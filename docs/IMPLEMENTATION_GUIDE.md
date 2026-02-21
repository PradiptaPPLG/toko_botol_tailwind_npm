# Implementation Guide - 13 Requirements

## ✅ COMPLETED TASKS

### 1. Database Migration
- File: `migration_requirements_update.sql`
- Added columns:
  - `stok_masuk`: `harga_beli_satuan`, `total_belanja`
  - `transaksi`: `asal`, `tujuan`
  - `stock_opname`: `is_cancelled`
- **ACTION REQUIRED**: Run this SQL file in phpMyAdmin

### 2. Custom Modal Confirmation (Requirement 4)
- File: `includes/layout_header.php`
- Functions added:
  - `customConfirm(message, title, icon, btnColor)`
  - `confirmDelete()`
  - `confirmSave()`
  - `confirmCancel()`
  - `confirmLogout()`
- Already replaced logout confirm in sidebar

### 3. Sidebar Dropdowns (Requirements 1, 13)
- File: `includes/layout_sidebar.php`
- Added Info Cabang dropdown with branch list
- Added Laporan dropdown (Umum, Penjualan, Pembelian)
- Added JavaScript toggle function

### 4. Info Cabang Page (Requirement 1)
- File: `modules/admin/info_cabang.php`
- Shows branch details and stock per product
- NO transaction recap (only inventory info)

### 5. Laporan Split (Requirement 13)
- Files created:
  - `modules/admin/laporan_penjualan.php` (only pembeli)
  - `modules/admin/laporan_pembelian.php` (only penjual)

---

## 🔄 REMAINING TASKS

### Task 6: Update tambah_stok.php (Requirement 5)
Location: `modules/admin/tambah_stok.php`

Changes needed:
1. Remove "Stok: X" display in product cards (line 106)
2. Remove stok_gudang input field, always set to 0 (line 83-85)
3. Add Edit and Delete buttons in product list
4. Make right panel scrollable with fixed height
5. Add edit modal with form
6. Add delete functionality with custom confirm

Code snippets:
```php
// Remove lines 83-85 (stok_gudang input)
// Change line 18: $stok_gudang = 0; (hardcode)

// In product cards, replace lines 100-113 with:
<div class="bg-gray-50 p-4 rounded-lg border-l-8 border-blue-500">
    <div class="flex justify-between items-start">
        <div class="flex-1">
            <p class="font-bold text-lg"><?= $p['nama_produk'] ?></p>
            <p class="text-sm text-gray-600">Kode: <?= $p['kode_produk'] ?></p>
            <div class="grid grid-cols-2 gap-2 mt-2 text-sm">
                <div>Beli: <?= rupiah($p['harga_beli']) ?></div>
                <div>Jual: <?= rupiah($p['harga_jual']) ?></div>
            </div>
            <div class="text-sm mt-1">Dus: <?= rupiah($p['harga_dus']) ?></div>
        </div>
        <div class="flex flex-col gap-2">
            <button onclick="editProduct(<?= $p['id'] ?>)"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                ✏️ Edit
            </button>
            <button onclick="deleteProduct(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_produk']) ?>')"
                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                🗑️ Hapus
            </button>
        </div>
    </div>
</div>

// Make right panel scrollable (line 93-117):
<div class="bg-white rounded-lg shadow p-6 max-h-[600px] overflow-y-auto">
```

Add JavaScript at bottom:
```javascript
async function deleteProduct(id, nama) {
    const confirmed = await confirmDelete(`Hapus produk "${nama}"?`);
    if (confirmed) {
        window.location.href = '?delete=' + id;
    }
}

function editProduct(id) {
    // Implementation for edit modal
    // Fetch product data and show in modal
}
```

Add PHP handler:
```php
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    execute("DELETE FROM produk WHERE id = $id");
    // CASCADE will handle stok_cabang deletion
    $success = "Produk berhasil dihapus!";
}
```

---

### Task 7: Update Gudang with Branch Grouping + AJAX (Requirements 2, 3, 8, 9, 10, 12)
Location: `modules/gudang/index.php`

This is the LARGEST task. Changes:

#### 7a. Group Products by Branch (Req 2)
Replace product grid (lines 248-259) with:
```php
<?php foreach ($cabang as $c): ?>
<div class="branch-container mb-4 border-2 border-gray-300 rounded-lg p-4 bg-gray-50">
    <h3 class="font-bold text-lg mb-3 text-blue-700">
        🏢 <?= $c['nama_cabang'] ?>
    </h3>
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
        <?php foreach ($produk as $p):
            $stok_cabang_val = get_stok_cabang($p['id'], $c['id']);
        ?>
        <div class="product-card bg-white border-2 border-gray-200 rounded-lg p-3"
             onclick="addToCartMasuk(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_produk']) ?>', <?= $p['stok_gudang'] ?>, <?= $c['id'] ?>, '<?= $c['nama_cabang'] ?>')">
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg h-20 flex items-center justify-center mb-2">
                <span class="text-3xl">🥤</span>
            </div>
            <h3 class="font-bold text-sm mb-1 truncate"><?= $p['nama_produk'] ?></h3>
            <p class="text-xs text-gray-600">Gudang: <?= $p['stok_gudang'] ?></p>
            <p class="text-xs text-green-600">Cabang: <?= $stok_cabang_val ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
```

#### 7b. Add Total Belanja Input (Req 8)
In cart items, add price input field. Update `renderCartMasuk()` JavaScript:
```javascript
html += `
    <div class="border rounded-lg p-2 bg-gray-50">
        <div class="flex justify-between items-start mb-2">
            <div class="flex-1">
                <h4 class="font-semibold text-sm">${item.nama}</h4>
                <p class="text-xs text-gray-600">Stok: ${item.stok_gudang} btl</p>
            </div>
            <button onclick="cartMasuk.splice(${index}, 1); renderCartMasuk();"
                    class="text-red-500 hover:text-red-700 text-sm">✕</button>
        </div>
        <div class="mb-2">
            <label class="text-xs text-gray-600">Harga Beli/Satuan:</label>
            <input type="number" class="w-full border rounded p-1 text-sm"
                   value="${item.harga_beli || 0}"
                   onchange="cartMasuk[${index}].harga_beli = parseInt(this.value); renderCartMasuk();"
                   placeholder="Rp">
        </div>
        <div class="flex items-center gap-2 mb-2">
            <button onclick="updateQtyMasuk(${index}, -1)"
                    class="qty-btn bg-gray-300 hover:bg-gray-400 w-7 h-7 rounded flex items-center justify-center font-bold">−</button>
            <input type="number" class="font-bold w-12 text-center border rounded"
                   value="${item.jumlah}"
                   onchange="cartMasuk[${index}].jumlah = Math.max(1, parseInt(this.value) || 1); renderCartMasuk();">
            <button onclick="updateQtyMasuk(${index}, 1)"
                    class="qty-btn bg-green-600 hover:bg-green-700 text-white w-7 h-7 rounded flex items-center justify-center font-bold">+</button>
        </div>
        <div class="text-xs text-right">
            <span class="font-bold">Total: Rp ${((item.harga_beli || 0) * item.jumlah).toLocaleString('id-ID')}</span>
        </div>
    </div>
`;
```

#### 7c. Convert to AJAX (Req 10)
Replace form submit with:
```javascript
document.getElementById('form-stok-masuk').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (cartMasuk.length === 0) {
        alert('Keranjang masih kosong!');
        return;
    }

    const formData = new FormData(this);
    formData.append('cart_data', JSON.stringify(cartMasuk));

    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });

        if (response.ok) {
            // Show success message without refresh
            showToast('✅ Stok masuk berhasil!', 'success');
            cartMasuk = [];
            renderCartMasuk();
        }
    } catch (error) {
        showToast('❌ Terjadi kesalahan!', 'error');
    }
});
```

#### 7d. Stock Opname Fixes (Req 3)
- Add cancel button in form (line 430)
- Add cancel functionality in history
- Clear success message on new input

```php
// Add to form:
<button type="button" onclick="document.getElementById('stock-opname-form').reset()"
        class="mt-4 w-full bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 rounded-lg text-lg">
    ❌ BATAL
</button>

// In history, add cancel button for each record:
<?php if ($so['is_cancelled'] == 0): ?>
<button onclick="cancelStockOpname(<?= $so['id'] ?>)"
        class="text-xs bg-red-500 text-white px-2 py-1 rounded">
    ❌ Cancel
</button>
<?php else: ?>
<span class="text-xs bg-gray-300 px-2 py-1 rounded">Dibatalkan</span>
<?php endif; ?>
```

---

### Task 8: Update Kasir (Requirements 6, 7, 10, 12)
Location: `modules/kasir/index.php`

#### 8a. Custom Dus Price Input (Req 6)
Update cart rendering (lines 383-427):
```javascript
${transactionType === 'pembeli' ? `
<div class="flex gap-1 mb-2">
    <button onclick="changeSatuan(${index}, 'botol')"
            class="flex-1 py-1 px-2 text-xs rounded ${item.satuan === 'botol' ? 'bg-blue-600 text-white' : 'bg-gray-200'}">
        Botol
    </button>
    <button onclick="changeSatuan(${index}, 'dus')"
            ${item.stok < 12 ? 'disabled title="Stok tidak cukup untuk 1 dus"' : ''}
            class="flex-1 py-1 px-2 text-xs rounded ${item.satuan === 'dus' ? 'bg-blue-600 text-white' : 'bg-gray-200'} ${item.stok < 12 ? 'opacity-50 cursor-not-allowed' : ''}">
        Dus
    </button>
</div>
${item.satuan === 'dus' ? `
<div class="mb-2">
    <label class="text-xs">Harga Dus:</label>
    <input type="number" class="w-full border rounded p-1 text-sm"
           value="${item.harga_dus_custom || item.harga_dus}"
           onchange="cart[${index}].harga_dus_custom = parseInt(this.value); renderCart();"
           placeholder="Masukkan harga">
</div>
` : ''}
` : ''}
```

#### 8b. Dus Stock Validation (Req 7)
```javascript
function changeSatuan(index, satuan) {
    if (satuan === 'dus' && cart[index].stok < 12) {
        alert('⚠️ Stok tidak mencukupi untuk transaksi per dus!\nMinimal 12 botol diperlukan.');
        return;
    }
    cart[index].satuan = satuan;
    renderCart();
}
```

#### 8c. Manual Quantity Input (Req 12)
Replace quantity display with editable input:
```javascript
<input type="number" class="font-bold w-12 text-center border rounded"
       value="${item.jumlah}"
       min="1"
       max="${satuan === 'dus' ? Math.floor(item.stok / 12) : item.stok}"
       onchange="cart[${index}].jumlah = Math.max(1, parseInt(this.value) || 1); renderCart();">
```

#### 8d. AJAX Submit (Req 10)
Same approach as gudang - prevent default and use fetch API.

---

## 📝 FINAL STEPS

After implementing all tasks:

1. **Run database migration**:
   ```sql
   SOURCE migration_requirements_update.sql;
   ```

2. **Replace all `confirm()` calls**:
   - Search for `confirm(` in all files
   - Replace with appropriate `customConfirm()`, `confirmDelete()`, etc.
   - Already done for logout in sidebar

3. **Test each feature thoroughly**:
   - ✅ Info Cabang dropdown
   - ✅ Laporan split
   - ⏳ Product edit/delete
   - ⏳ Gudang branch grouping
   - ⏳ Gudang AJAX forms
   - ⏳ Kasir dus validation
   - ⏳ Manual quantity inputs
   - ⏳ Stock opname cancel

4. **Verify no page refreshes** on form submissions

---

## 🎯 QUICK REFERENCE

**Files Modified:**
- ✅ includes/layout_header.php (modal)
- ✅ includes/layout_sidebar.php (dropdowns)
- ⏳ modules/admin/tambah_stok.php
- ⏳ modules/gudang/index.php
- ⏳ modules/kasir/index.php

**Files Created:**
- ✅ migration_requirements_update.sql
- ✅ modules/admin/info_cabang.php
- ✅ modules/admin/laporan_penjualan.php
- ✅ modules/admin/laporan_pembelian.php
- ✅ IMPLEMENTATION_GUIDE.md (this file)

**Priority Order:**
1. Run database migration first!
2. Update tambah_stok.php (smaller task)
3. Update kasir (medium complexity)
4. Update gudang (largest task)
5. Test everything
