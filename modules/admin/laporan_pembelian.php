<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
if (!is_login() || !is_admin()) redirect('../../login.php');

$title = 'Laporan Stok';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';

$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-d');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$tipe_filter = $_GET['tipe'] ?? 'semua'; // semua, masuk, rusak, transfer

$cabang_list = get_cabang();

// Get stok masuk
$stok_masuk = query("
    SELECT
        sm.id,
        sm.created_at,
        'Stok Masuk' as tipe,
        '📦' as icon,
        p.nama_produk,
        sm.jumlah,
        sm.keterangan,
        NULL as cabang_tujuan_id,
        NULL as cabang_tujuan_nama,
        'bg-green-100 text-green-800' as badge_color
    FROM stok_masuk sm
    JOIN produk p ON sm.produk_id = p.id
    WHERE DATE(sm.created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
    " . ($tipe_filter == 'masuk' ? "" : ($tipe_filter == 'semua' ? "" : "AND 1=0")) . "
    ORDER BY sm.created_at DESC
");

// Get stok rusak
$stok_rusak = query("
    SELECT
        sk.id,
        sk.created_at,
        'Stok Rusak' as tipe,
        '❌' as icon,
        p.nama_produk,
        sk.jumlah,
        sk.keterangan,
        NULL as cabang_tujuan_id,
        NULL as cabang_tujuan_nama,
        'bg-red-100 text-red-800' as badge_color
    FROM stok_keluar sk
    JOIN produk p ON sk.produk_id = p.id
    WHERE sk.kondisi = 'rusak'
      AND DATE(sk.created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
    " . ($tipe_filter == 'rusak' ? "" : ($tipe_filter == 'semua' ? "" : "AND 1=0")) . "
    ORDER BY sk.created_at DESC
");

// Get stok transfer
$stok_transfer = query("
    SELECT
        sk.id,
        sk.created_at,
        'Transfer' as tipe,
        '🚚' as icon,
        p.nama_produk,
        sk.jumlah,
        sk.keterangan,
        sk.cabang_tujuan as cabang_tujuan_id,
        (SELECT nama_cabang FROM cabang WHERE id = sk.cabang_tujuan) as cabang_tujuan_nama,
        'bg-blue-100 text-blue-800' as badge_color
    FROM stok_keluar sk
    JOIN produk p ON sk.produk_id = p.id
    WHERE sk.kondisi = 'transfer'
      AND DATE(sk.created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
    " . ($tipe_filter == 'transfer' ? "" : ($tipe_filter == 'semua' ? "" : "AND 1=0")) . "
    ORDER BY sk.created_at DESC
");

// Combine all data
$all_movements = [];
if ($tipe_filter == 'semua' || $tipe_filter == 'masuk') {
    $all_movements = array_merge($all_movements, $stok_masuk);
}
if ($tipe_filter == 'semua' || $tipe_filter == 'rusak') {
    $all_movements = array_merge($all_movements, $stok_rusak);
}
if ($tipe_filter == 'semua' || $tipe_filter == 'transfer') {
    $all_movements = array_merge($all_movements, $stok_transfer);
}

// Sort by date descending
usort($all_movements, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Calculate totals
$total_masuk = count($stok_masuk);
$total_rusak = count($stok_rusak);
$total_transfer = count($stok_transfer);
$jumlah_masuk = array_sum(array_column($stok_masuk, 'jumlah'));
$jumlah_rusak = array_sum(array_column($stok_rusak, 'jumlah'));
$jumlah_transfer = array_sum(array_column($stok_transfer, 'jumlah'));

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 30;
$offset = ($page - 1) * $limit;
$total_data = count($all_movements);
$total_pages = ceil($total_data / $limit);
$movements_page = array_slice($all_movements, $offset, $limit);
?>
<div class="p-6">
    <h1 class="judul text-3xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="mr-3">📦</span> LAPORAN RIWAYAT STOK
    </h1>

    <!-- Filter -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-gray-700 font-medium mb-2">📅 Tanggal Mulai</label>
                <label>
                    <input type="date" name="tanggal_mulai" value="<?= $tanggal_mulai ?>" class="w-full border rounded-lg p-3 text-lg">
                </label>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">📅 Tanggal Akhir</label>
                <label>
                    <input type="date" name="tanggal_akhir" value="<?= $tanggal_akhir ?>" class="w-full border rounded-lg p-3 text-lg">
                </label>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">📋 Tipe</label>
                <label>
                    <select name="tipe" class="w-full border rounded-lg p-3 text-lg">
                        <option value="semua" <?= $tipe_filter == 'semua' ? 'selected' : '' ?>>Semua</option>
                        <option value="masuk" <?= $tipe_filter == 'masuk' ? 'selected' : '' ?>>Stok Masuk</option>
                        <option value="rusak" <?= $tipe_filter == 'rusak' ? 'selected' : '' ?>>Stok Rusak</option>
                        <option value="transfer" <?= $tipe_filter == 'transfer' ? 'selected' : '' ?>>Transfer Cabang</option>
                    </select>
                </label>
            </div>
            <div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg text-lg">
                    🔍 TAMPILKAN
                </button>
            </div>
        </form>
    </div>

    <!-- Rekap Total -->
    <div class="bg-linear-to-r from-indigo-700 to-purple-900 text-black p-8 rounded-lg shadow-lg mb-6">
        <h2 class="text-2xl font-bold mb-6 flex items-center">
            <span class="mr-3">📊</span> REKAP PERGERAKAN STOK <?= date('d/m/Y', strtotime($tanggal_mulai)) ?> - <?= date('d/m/Y', strtotime($tanggal_akhir)) ?>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white bg-opacity-20 p-6 rounded-lg">
                <p class="text-sm opacity-90">📦 STOK MASUK</p>
                <p class="text-5xl font-bold mt-2"><?= $total_masuk ?></p>
                <p class="text-xs mt-2 opacity-75"><?= number_format($jumlah_masuk) ?> botol</p>
            </div>
            <div class="bg-white bg-opacity-20 p-6 rounded-lg">
                <p class="text-sm opacity-90">❌ STOK RUSAK</p>
                <p class="text-5xl font-bold mt-2"><?= $total_rusak ?></p>
                <p class="text-xs mt-2 opacity-75"><?= number_format($jumlah_rusak) ?> botol</p>
            </div>
            <div class="bg-white bg-opacity-20 p-6 rounded-lg">
                <p class="text-sm opacity-90">🚚 TRANSFER</p>
                <p class="text-5xl font-bold mt-2"><?= $total_transfer ?></p>
                <p class="text-xs mt-2 opacity-75"><?= number_format($jumlah_transfer) ?> botol</p>
            </div>
        </div>
    </div>

    <!-- Riwayat Pergerakan Stok -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gray-800 text-white p-4">
            <h2 class="text-xl font-bold">📋 RIWAYAT PERGERAKAN STOK</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Waktu</th>
                        <th class="p-3 text-left">Tipe</th>
                        <th class="p-3 text-left">Produk</th>
                        <th class="p-3 text-left">Jumlah</th>
                        <th class="p-3 text-left">Keterangan</th>
                        <th class="p-3 text-left">Tujuan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($movements_page)): ?>
                        <?php foreach ($movements_page as $m): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3">
                                <?= date('d/m/Y H:i', strtotime($m['created_at'])) ?>
                            </td>
                            <td class="p-3">
                                <span class="px-3 py-1 rounded-full text-xs font-bold <?= $m['badge_color'] ?>">
                                    <?= $m['icon'] ?> <?= $m['tipe'] ?>
                                </span>
                            </td>
                            <td class="p-3 font-semibold"><?= $m['nama_produk'] ?></td>
                            <td class="p-3">
                                <span class="font-bold text-lg"><?= number_format($m['jumlah']) ?></span>
                                <span class="text-sm text-gray-600">botol</span>
                            </td>
                            <td class="p-3 text-sm text-gray-700">
                                <?= $m['keterangan'] ?? '-' ?>
                            </td>
                            <td class="p-3">
                                <?php if ($m['cabang_tujuan_nama']): ?>
                                    <span class="text-sm font-semibold text-blue-600">
                                        🏢 <?= $m['cabang_tujuan_nama'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-500">
                                Belum ada riwayat pergerakan stok
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="bg-gray-50 p-4 flex justify-center">
            <div class="flex space-x-2">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?tanggal_mulai=<?= $tanggal_mulai ?>&tanggal_akhir=<?= $tanggal_akhir ?>&tipe=<?= $tipe_filter ?>&page=<?= $i ?>"
                   class="px-4 py-2 <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?> rounded-lg">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Note -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-6 rounded-lg">
        <h3 class="font-bold text-blue-900 mb-2">📌 Keterangan:</h3>
        <ul class="text-sm text-gray-700 space-y-1">
            <li><strong>📦 Stok Masuk:</strong> Barang yang masuk ke gudang dari supplier</li>
            <li><strong>❌ Stok Rusak:</strong> Barang yang rusak atau tidak layak jual</li>
            <li><strong>🚚 Transfer:</strong> Barang yang dipindahkan antar cabang</li>
        </ul>
    </div>
</div>

<?php include '../../includes/layout_footer.php'; ?>
