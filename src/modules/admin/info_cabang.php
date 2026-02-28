<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
if (!is_login() || !is_admin()) redirect('../../login.php');

$cabang_id = $_GET['cabang'] ?? 1;
$cabang_info = query("SELECT * FROM cabang WHERE id = $cabang_id")[0] ?? null;

if (!$cabang_info) {
    redirect('info_cabang.php?cabang=1');
}

$title = 'Info Cabang - ' . $cabang_info['nama_cabang'];
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';
include '../../includes/modal_confirm.php';

// Get stock per product for this branch
$stok_cabang = query("
    SELECT p.*, sc.stok as stok_cabang
    FROM produk p
    LEFT JOIN stok_cabang sc ON p.id = sc.produk_id AND sc.cabang_id = $cabang_id
    ORDER BY p.nama_produk
");

// Calculate totals
$total_stok = array_sum(array_column($stok_cabang, 'stok_cabang'));
$total_produk = count($stok_cabang);
$total_modal = array_sum(array_map(function($item) {
    return $item['harga_beli'] * ($item['stok_cabang'] ?? 0);
}, $stok_cabang));
?>

<div class="p-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h1 class="judul text-3xl font-bold text-gray-800 flex items-center">
            <span class="mr-3">🏢</span> INFO CABANG: <?= $cabang_info['nama_cabang'] ?>
        </h1>
        <p class="text-gray-600 mt-2">📍 <?= $cabang_info['alamat'] ?></p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-linear-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow p-6">
            <p class="text-sm opacity-90">Total Produk</p>
            <p class="text-4xl font-bold mt-2"><?= $total_produk ?></p>
            <p class="text-xs mt-1 opacity-75">Jenis produk</p>
        </div>
        <div class="bg-linear-to-br from-green-500 to-green-600 text-white rounded-lg shadow p-6">
            <p class="text-sm opacity-90">Total Stok</p>
            <p class="text-4xl font-bold mt-2"><?= $total_stok ?></p>
            <p class="text-xs mt-1 opacity-75">Botol</p>
        </div>
        <div class="bg-linear-to-br from-purple-500 to-blue-600 text-white rounded-lg shadow p-6">
            <p class="text-sm opacity-90">Total Modal</p>
            <p class="text-2xl font-bold mt-2"><?= rupiah($total_modal) ?></p>
            <p class="text-xs mt-1 opacity-75">Σ (Harga Beli × Stok)</p>
        </div>
    </div>

    <!-- Stock Details -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gray-800 text-white p-4">
            <h2 class="text-xl font-bold">📦 DETAIL STOK PRODUK</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($stok_cabang as $item): ?>
                <div class="bg-gray-50 rounded-lg border-2 border-gray-200 p-4 hover:shadow-lg transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-3xl">🥤</span>
                        <span class="<?= $item['stok_cabang'] > 20 ? 'bg-green-100 text-green-800' : ($item['stok_cabang'] > 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>
                                     px-3 py-1 rounded-full text-sm font-bold">
                            <?= $item['stok_cabang'] ?? 0 ?> botol
                        </span>
                    </div>
                    <h3 class="font-bold text-lg mb-2"><?= $item['nama_produk'] ?></h3>
                    <p class="text-xs text-gray-500 mb-2">Kode: <?= $item['kode_produk'] ?></p>
                    <div class="border-t pt-3 mt-3 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Harga Beli Satuan:</span>
                            <span class="font-bold"><?= rupiah($item['harga_beli']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Modal:</span>
                            <span class="font-bold text-red-600"><?= rupiah($item['harga_beli'] * ($item['stok_cabang'] ?? 0)) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Stok Gudang:</span>
                            <span class="font-semibold text-blue-600"><?= $item['stok_gudang'] ?> botol</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($stok_cabang)): ?>
            <p class="text-center text-gray-500 py-12">Belum ada stok produk di cabang ini</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Legend -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-6 rounded-lg">
        <h3 class="font-bold text-blue-900 mb-2">📌 Keterangan:</h3>
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center">
                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-bold mr-2">20+</span>
                <span class="text-gray-700">Stok Aman</span>
            </div>
            <div class="flex items-center">
                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-bold mr-2">11-20</span>
                <span class="text-gray-700">Stok Menipis</span>
            </div>
            <div class="flex items-center">
                <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-bold mr-2">0-10</span>
                <span class="text-gray-700">Stok Kritis</span>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/layout_footer.php'; ?>
