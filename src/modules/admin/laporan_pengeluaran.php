<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
if (!is_login() || !is_admin()) redirect('../../login.php');

$title = 'Laporan Pengeluaran';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';
include '../../includes/modal_confirm.php';

$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$filter_keterangan = $_GET['keterangan'] ?? '';

$sql_keterangan = '';
if ($filter_keterangan !== '') {
    $filter_keterangan_escaped = escape_string($filter_keterangan);
    $sql_keterangan = " AND keterangan LIKE '%$filter_keterangan_escaped%'";
}

$pengeluaran = query("
    SELECT *
    FROM pengeluaran
    WHERE DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
    $sql_keterangan
    ORDER BY created_at DESC
");

$total_pengeluaran = array_sum(array_column($pengeluaran, 'nominal'));
?>
<div class="p-6">
    <h1 class="judul text-3xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="mr-3">💸</span> LAPORAN PENGELUARAN
    </h1>

    <!-- Filter -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" class="flex flex-wrap md:flex-nowrap items-end gap-3">
            <div class="flex-1 min-w-35">
                <label class="block text-gray-700 font-medium mb-1 text-xs uppercase tracking-wider">📅 Mulai</label>
                <label>
                    <input type="date" name="tanggal_mulai" value="<?= $tanggal_mulai ?>" class="w-full border rounded-lg p-2 text-sm">
                </label>
            </div>
            <div class="flex-1 min-w-35">
                <label class="block text-gray-700 font-medium mb-1 text-xs uppercase tracking-wider">📅 Akhir</label>
                <label>
                    <input type="date" name="tanggal_akhir" value="<?= $tanggal_akhir ?>" class="w-full border rounded-lg p-2 text-sm">
                </label>
            </div>
            <div class="flex-1 min-w-[140px]">
                <label class="block text-gray-700 font-medium mb-1 text-xs uppercase tracking-wider">🔎 Keterangan</label>
                <label>
                    <input type="text" name="keterangan" value="<?= htmlspecialchars($filter_keterangan) ?>" class="w-full border rounded-lg p-2 text-sm" placeholder="Cari keterangan...">
                </label>
            </div>
            <div class="w-full md:w-auto">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg text-sm">🔍 CARI</button>
            </div>
        </form>
    </div>

    <!-- Summary Total Section -->
    <div class="bg-linear-to-r from-indigo-700 to-purple-900 text-black p-8 rounded-lg shadow-lg mb-6">
        <h2 class="text-2xl font-bold mb-6 flex items-center text-white">
            <span class="mr-3">📊</span> REKAP PENGELUARAN <?= date('d/m/Y', strtotime($tanggal_mulai)) ?> - <?= date('d/m/Y', strtotime($tanggal_akhir)) ?>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white bg-opacity-20 p-6 rounded-lg border border-white border-opacity-20">
                <p class="text-sm opacity-90 font-bold uppercase tracking-wider">💸 TOTAL PENGELUARAN</p>
                <p class="text-4xl md:text-5xl font-bold mt-2 font-mono"><?= rupiah($total_pengeluaran) ?></p>
            </div>
            <div class="bg-white bg-opacity-20 p-6 rounded-lg border border-white border-opacity-20">
                <p class="text-sm opacity-90 font-bold uppercase tracking-wider">🧾 JUMLAH TRANSAKSI</p>
                <p class="text-4xl md:text-5xl font-bold mt-2 font-mono"><?= count($pengeluaran) ?></p>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gray-800 text-white p-4">
            <h2 class="text-xl font-bold">📋 DAFTAR PENGELUARAN</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Waktu</th>
                        <th class="p-3 text-left">Keterangan</th>
                        <th class="p-3 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pengeluaran)): ?>
                        <?php foreach ($pengeluaran as $p): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 text-sm">
                                <?= date('d/m/y', strtotime($p['created_at'])) ?>
                                <span class="text-gray-400"><?= date('H:i', strtotime($p['created_at'])) ?></span>
                            </td>
                            <td class="p-3 text-sm text-gray-800">
                                <?= htmlspecialchars($p['keterangan']) ?>
                            </td>
                            <td class="p-3 text-right font-bold text-red-600">
                                <?= rupiah($p['nominal']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="p-8 text-center text-gray-500">
                                Tidak ada pengeluaran pada periode ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-6 rounded-lg text-sm text-blue-900">
        <h3 class="font-bold mb-1">📌 Catatan:</h3>
        <p>Laporan ini mengambil data langsung dari tabel <span class="font-mono font-semibold">pengeluaran</span> yang dicatat melalui menu <span class="font-semibold">Pengeluaran</span>.</p>
    </div>
</div>

<?php include '../../includes/layout_footer.php'; ?>

