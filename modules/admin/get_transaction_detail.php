<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!is_login() || !is_admin()) {
    die('Unauthorized');
}

$invoice = $_GET['invoice'] ?? '';
if (empty($invoice)) {
    die('Invoice not provided');
}

// Check if using new structure
$use_new_structure = false;
$check_new_table = query("SHOW TABLES LIKE 'transaksi_header'");
if (count($check_new_table) > 0) {
    $use_new_structure = true;
}

if ($use_new_structure) {
    // NEW STRUCTURE
    $transaction = get_transaction_by_invoice($invoice);

    if (!$transaction) {
        echo '<p class="text-red-500 text-center">Transaksi tidak ditemukan</p>';
        exit;
    }

    $header = $transaction['header'];
    $details = $transaction['details'];
    $cabang_info = query("SELECT * FROM cabang WHERE id = {$header['cabang_id']}")[0] ?? null;

} else {
    // OLD STRUCTURE (Fallback)
    $items = query("
        SELECT t.*, p.nama_produk
        FROM transaksi t
        JOIN produk p ON t.produk_id = p.id
        WHERE t.no_invoice = '" . escape_string($invoice) . "'
        ORDER BY t.id
    ");

    if (empty($items)) {
        echo '<p class="text-red-500 text-center">Transaksi tidak ditemukan</p>';
        exit;
    }

    // Construct header from aggregated data
    $first_item = $items[0];
    $header = [
        'no_invoice' => $first_item['no_invoice'],
        'cabang_id' => $first_item['cabang_id'],
        'nama_kasir' => $first_item['nama_kasir'],

        'total_items' => count($items),
        'total_harga' => array_sum(array_column($items, 'total_harga')),
        'created_at' => $first_item['created_at']
    ];

    $details = $items;
    $cabang_info = query("SELECT * FROM cabang WHERE id = {$header['cabang_id']}")[0] ?? null;
}
?>

<!-- Struk / Invoice Detail -->
<div class="bg-white" id="strukContent">
    <!-- Header Toko -->
    <div class="text-center border-b-2 border-dashed border-gray-300 pb-4 mb-4">
        <h2 class="text-2xl font-bold">TOKO PDK</h2>
        <p class="text-sm"><?= $cabang_info ? $cabang_info['alamat'] : '' ?></p>
        <p class="text-sm font-semibold mt-1">Cabang: <?= $cabang_info ? $cabang_info['nama_cabang'] : 'N/A' ?></p>
    </div>

    <!-- Info Transaksi -->
    <div class="mb-4 text-sm space-y-1">
        <div class="flex justify-between">
            <span>No Invoice:</span>
            <span class="font-mono font-bold"><?= $header['no_invoice'] ?></span>
        </div>
        <div class="flex justify-between">
            <span>Tanggal:</span>
            <span><?= date('d/m/Y H:i:s', strtotime($header['created_at'])) ?></span>
        </div>
        <div class="flex justify-between">
            <span>Kasir:</span>
            <span><?= $header['nama_kasir'] ?></span>
        </div>

    </div>

    <!-- Items -->
    <div class="border-t-2 border-b-2 border-dashed border-gray-300 py-4 mb-4">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b">
                    <th class="text-left pb-2">Item</th>
                    <th class="text-center pb-2">Qty</th>
                    <th class="text-right pb-2">Harga</th>
                    <th class="text-right pb-2">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($details as $item): ?>
                <tr class="border-b">
                    <td class="py-2">
                        <div class="font-semibold"><?= $item['nama_produk'] ?></div>

                    </td>
                    <td class="py-2 text-center">
                        <?= number_format($item['jumlah'], 0, ',', '.') ?> <?= $item['satuan'] ?>
                    </td>
                    <td class="py-2 text-right">
                        <?= rupiah($item['harga_satuan']) ?>
                    </td>
                    <td class="py-2 text-right font-semibold">
                        <?= rupiah($item['subtotal'] ?? $item['total_harga']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Total -->
    <div class="space-y-2 mb-4">
        <div class="flex justify-between text-lg font-bold">
            <span>TOTAL:</span>
            <span class="text-green-600"><?= rupiah($header['total_harga']) ?></span>
        </div>

        <?php if (isset($header['total_bayar']) && $header['total_bayar']): ?>
        <div class="flex justify-between">
            <span>Bayar:</span>
            <span><?= rupiah($header['total_bayar']) ?></span>
        </div>
        <div class="flex justify-between font-semibold">
            <span>Kembali:</span>
            <span class="text-blue-600"><?= rupiah($header['kembalian'] ?? 0) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="text-center border-t-2 border-dashed border-gray-300 pt-4 text-sm">
        <p class="font-semibold">Terima Kasih Atas Kunjungan Anda</p>
        <p class="text-xs text-gray-600 mt-2">Barang yang sudah dibeli tidak dapat dikembalikan</p>
    </div>

    <!-- Action Buttons (No Print) -->
    <div class="mt-6 flex gap-3 no-print">
        <button onclick="window.print()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg">
            🖨️ Print Struk
        </button>
        <button onclick="closeDetailModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-4 rounded-lg">
            Tutup
        </button>
    </div>
</div>
