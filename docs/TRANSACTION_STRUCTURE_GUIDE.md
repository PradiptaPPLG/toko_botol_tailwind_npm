# Transaction Structure - Header & Detail System

## 🎯 Overview

The transaction system has been redesigned to use proper **header-detail** structure:
- **Before**: One flat `transaksi` table (all items mixed together)
- **After**: `transaksi_header` (invoice/struk) + `transaksi_detail` (individual items)

---

## 📋 New Database Structure

### 1. `transaksi_header` (Invoice/Struk)
**One row per transaction**

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| no_invoice | VARCHAR(50) | Unique invoice number |
| cabang_id | INT | Branch ID |
| session_kasir_id | INT | Cashier session |
| nama_kasir | VARCHAR(100) | Cashier name |
| tipe | ENUM | 'pembeli' or 'penjual' |
| total_items | INT | Number of items |
| total_harga | INT | Total price |
| total_bayar | INT | Amount paid |
| kembalian | INT | Change |
| created_at | DATETIME | Transaction time |

### 2. `transaksi_detail` (Transaction Items)
**Multiple rows per transaction (one per item)**

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| transaksi_header_id | INT | FK to transaksi_header |
| no_invoice | VARCHAR(50) | Invoice number |
| produk_id | INT | Product ID |
| nama_produk | VARCHAR(100) | Product name (snapshot) |
| jumlah | INT | Quantity |
| satuan | VARCHAR(20) | Unit (botol/dus) |
| harga_satuan | INT | Unit price |
| harga_tawar | INT | Negotiated price (for penjual) |
| selisih | INT | Price difference |
| subtotal | INT | Line total |
| created_at | DATETIME | Item added time |

---

## 🚀 Migration Steps

### Step 1: Run Migration
```bash
# In phpMyAdmin, import:
migration_transaction_structure.sql
```

### Step 2: Verify Data
```sql
-- Check headers
SELECT COUNT(*) FROM transaksi_header;

-- Check details
SELECT COUNT(*) FROM transaksi_detail;

-- Compare with old table
SELECT COUNT(DISTINCT no_invoice) FROM transaksi;
```

### Step 3: Backup Old Table (After Verification)
```sql
-- Rename old table (SAFE - no data loss)
RENAME TABLE `transaksi` TO `transaksi_old_backup`;
```

---

## 💻 New PHP Functions

### 1. Save Transaction
```php
// In includes/functions.php

$header_data = [
    'no_invoice' => generate_invoice(),
    'cabang_id' => $cabang_id,
    'session_kasir_id' => $session_id,
    'nama_kasir' => 'John Doe',
    'tipe' => 'pembeli',
    'total_harga' => 50000,
    'total_bayar' => 50000,
    'kembalian' => 0
];

$items = [
    [
        'produk_id' => 1,
        'jumlah' => 2,
        'satuan' => 'botol',
        'harga_satuan' => 3000,
        'subtotal' => 6000
    ],
    // ... more items
];

$result = save_transaction($header_data, $items);

if ($result['success']) {
    echo "Success! Invoice: " . $result['invoice'];
    // Redirect to struk page
    header("Location: struk.php?invoice=" . $result['invoice']);
} else {
    echo "Error: " . $result['message'];
}
```

### 2. Get Transaction
```php
$transaction = get_transaction_by_invoice('INV-20260221-1234');

echo "Invoice: " . $transaction['header']['no_invoice'];
echo "Total: " . rupiah($transaction['header']['total_harga']);

foreach ($transaction['details'] as $item) {
    echo $item['nama_produk'] . " x " . $item['jumlah'];
}
```

---

## 📄 Creating Struk (Receipt)

Create: `modules/kasir/struk.php`

```php
<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$invoice = $_GET['invoice'] ?? '';
$transaction = get_transaction_by_invoice($invoice);

if (!$transaction) {
    die('Invoice not found!');
}

$header = $transaction['header'];
$details = $transaction['details'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Struk - <?= $invoice ?></title>
    <style>
        @media print {
            .no-print { display: none; }
        }
        body { font-family: monospace; max-width: 300px; margin: 20px auto; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        .border-top { border-top: 1px dashed #000; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="text-center">
        <h2>TOKO SIMAMORA</h2>
        <p>Cabang: <?= $header['nama_cabang'] ?></p>
        <p>=============================</p>
    </div>

    <p>No: <?= $header['no_invoice'] ?></p>
    <p>Tanggal: <?= date('d/m/Y H:i', strtotime($header['created_at'])) ?></p>
    <p>Kasir: <?= $header['nama_kasir'] ?></p>
    <p>=============================</p>

    <table>
        <?php foreach ($details as $item): ?>
        <tr>
            <td><?= $item['nama_produk'] ?></td>
        </tr>
        <tr>
            <td><?= $item['jumlah'] ?> <?= $item['satuan'] ?> x <?= rupiah($item['harga_satuan']) ?></td>
            <td class="text-right"><?= rupiah($item['subtotal']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <p class="border-top">Total: <span class="text-right"><?= rupiah($header['total_harga']) ?></span></p>
    <p>Bayar: <span class="text-right"><?= rupiah($header['total_bayar']) ?></span></p>
    <p>Kembali: <span class="text-right"><?= rupiah($header['kembalian']) ?></span></p>

    <p class="text-center">=============================</p>
    <p class="text-center">Terima Kasih</p>

    <div class="no-print">
        <button onclick="window.print()">🖨️ Print</button>
        <button onclick="window.close()">Tutup</button>
    </div>
</body>
</html>
```

---

## 📊 Updating Laporan

### Laporan Penjualan (Grouped by Invoice)

```php
// Get headers grouped
$transaksi_headers = query("
    SELECT th.*, c.nama_cabang
    FROM transaksi_header th
    JOIN cabang c ON th.cabang_id = c.id
    WHERE th.tipe = 'pembeli'
      AND DATE(th.created_at) = '$tanggal'
    ORDER BY th.created_at DESC
");

// Display with expandable details
foreach ($transaksi_headers as $header) {
    echo "Invoice: " . $header['no_invoice'];
    echo "Total: " . rupiah($header['total_harga']);
    echo "Items: " . $header['total_items'];

    // Click to expand details
    echo '<button onclick="showDetails(\'' . $header['no_invoice'] . '\')">Detail</button>';
}
```

---

## ✅ Benefits

1. **✅ Proper Structure**: One invoice = one header + multiple details
2. **✅ Easy Reporting**: Group by invoice, expand for details
3. **✅ Struk Printing**: Complete transaction info in one query
4. **✅ Data Integrity**: Transaction atomicity with rollback
5. **✅ Scalability**: Better performance with proper indexes
6. **✅ Analytics**: Easier to analyze transaction patterns

---

## 🔄 Next Steps

1. ✅ Run `migration_transaction_structure.sql`
2. ⏳ Update kasir/index.php to use `save_transaction()`
3. ⏳ Create `struk.php` for receipt display
4. ⏳ Update `laporan_penjualan.php` to group by invoice
5. ⏳ Update `laporan_pembelian.php` to group by invoice
6. ⏳ Test transaction flow end-to-end
7. ✅ Verify old data migrated correctly
8. ✅ Backup old `transaksi` table

---

## ⚠️ Important Notes

- **Old `transaksi` table** is kept as backup (rename, don't drop!)
- **Migration is automatic** - existing data will be copied
- **New transactions** must use `save_transaction()` function
- **Struk can be reprinted** anytime using invoice number
- **Reports** now show invoice summaries with expandable details

---

Ready to implement! 🚀
