<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!is_login() || !is_admin()) redirect('../../login.php');

$title = 'Total Stok Cabang';

// Ambil total stok per produk dari semua cabang (hanya dari stok_cabang)
$stok_total = query("
    SELECT p.*, COALESCE(SUM(sc.stok), 0) AS total_stok
    FROM produk p
    LEFT JOIN stok_cabang sc ON p.id = sc.produk_id
    GROUP BY p.id
    ORDER BY p.nama_produk
");

$grand_total_stok = array_sum(array_column($stok_total, 'total_stok'));
$total_produk = count($stok_total);
$grand_total_modal = array_sum(array_map(function($item) {
    return $item['harga_beli'] * $item['total_stok'];
}, $stok_total));

include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';
include '../../includes/modal_confirm.php';
?>

<div class="p-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h1 class="judul text-3xl font-bold text-gray-800 flex items-center">
            <span class="mr-3">📦</span> TOTAL STOK CABANG
        </h1>
        <p class="text-gray-600 mt-2">
            Ringkasan total stok semua cabang (berdasarkan tabel stok_cabang, penjumlahan semua cabang per produk).
        </p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-linear-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow p-6">
            <p class="text-sm opacity-90">Total Produk</p>
            <p class="text-4xl font-bold mt-2"><?= $total_produk ?></p>
            <p class="text-xs mt-1 opacity-75">Jenis produk</p>
        </div>
        <div class="bg-linear-to-br from-green-500 to-green-600 text-white rounded-lg shadow p-6">
            <p class="text-sm opacity-90">Total Stok Semua Cabang</p>
            <p class="text-4xl font-bold mt-2"><?= $grand_total_stok ?></p>
            <p class="text-xs mt-1 opacity-75">Botol (akumulasi semua cabang)</p>
        </div>
        <div class="bg-linear-to-br from-blue-500 to-purple-600 text-white rounded-lg shadow p-6">
            <p class="text-sm opacity-90">Total Modal</p>
            <p class="text-2xl font-bold mt-2"><?= rupiah($grand_total_modal) ?></p>
            <p class="text-xs mt-1 opacity-75">Σ (Harga Beli × Stok)</p>
        </div>
    </div>

    <!-- Stock Details (global, mirip Info Cabang) -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gray-800 text-white p-4">
            <h2 class="text-xl font-bold">📦 DETAIL STOK PRODUK (TOTAL SEMUA CABANG)</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($stok_total as $item): ?>
                <div class="bg-gray-50 rounded-lg border-2 border-gray-200 p-4 hover:shadow-lg transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-3xl">🥤</span>
                        <?php
                        $total = (int)($item['total_stok'] ?? 0);
                        $badgeClass = $total > 20
                            ? 'bg-green-100 text-green-800'
                            : ($total > 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                        ?>
                        <span class="<?= $badgeClass ?> px-3 py-1 rounded-full text-sm font-bold">
                            <?= $total ?> botol
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
                            <span class="font-bold text-red-600"><?= rupiah($item['harga_beli'] * $total) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($stok_total)): ?>
            <p class="text-center text-gray-500 py-12">Belum ada data stok produk di cabang manapun.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Legend -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-6 rounded-lg">
        <h3 class="font-bold text-blue-900 mb-2">📌 Keterangan:</h3>
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center">
                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-bold mr-2">20+</span>
                <span class="text-gray-700">Stok Aman (total semua cabang)</span>
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
        <p class="text-xs text-blue-900 mt-3">
            Angka yang ditampilkan pada setiap produk adalah hasil penjumlahan stok di semua cabang (contoh: Barat 10 + Pusat 130 + Timur 30 = 170 botol).
        </p>
    </div>
</div>

<?php include '../../includes/layout_footer.php'; ?>

