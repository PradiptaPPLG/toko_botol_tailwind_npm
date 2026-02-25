<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
if (!is_login() || !is_admin()) redirect('../../login.php');

$title = 'Laporan Penjualan';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';
include '../../includes/modal_confirm.php';

$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-d');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$cabang_id = $_GET['cabang'] ?? 0;
$tipe_filter = $_GET['tipe'] ?? 'semua'; // semua, pembeli, penjual

$cabang_list = get_cabang();

// Check if using new structure or old structure
$use_new_structure = false;
$check_new_table = query("SHOW TABLES LIKE 'transaksi_header'");
if (count($check_new_table) > 0) {
    $use_new_structure = true;
}

if ($use_new_structure) {
    // NEW STRUCTURE: Use transaksi_header (grouped by invoice)

    // Rekap per cabang
    $rekap_cabang = [];
    foreach ($cabang_list as $c) {
        $rekap = query("
            SELECT
                COUNT(*) as total_transaksi,
                SUM(total_harga) as total_penjualan
            FROM transaksi_header
            WHERE cabang_id = {$c['id']}
              AND DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
        ")[0];

        $rekap_cabang[$c['id']] = [
            'nama' => $c['nama_cabang'],
            'transaksi' => $rekap['total_transaksi'] ?? 0,
            'penjualan' => $rekap['total_penjualan'] ?? 0
        ];
    }

    // Totals
    $total_penjualan = array_sum(array_column($rekap_cabang, 'penjualan'));
    $total_transaksi = array_sum(array_column($rekap_cabang, 'transaksi'));

    // Get transaction headers
    $transaksi_headers = query("
        SELECT th.*, c.nama_cabang
        FROM transaksi_header th
        JOIN cabang c ON th.cabang_id = c.id
        WHERE DATE(th.created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
        " . ($cabang_id > 0 ? " AND th.cabang_id = $cabang_id" : "") . "
        " . ($tipe_filter != 'semua' ? " AND th.tipe = '$tipe_filter'" : "") . "
        ORDER BY th.created_at DESC
    ");

} else {
    // OLD STRUCTURE Fallback (Simplified as this is legacy)
    $rekap_pembeli = query("SELECT SUM(total_harga) as total, COUNT(DISTINCT no_invoice) as count FROM transaksi WHERE tipe='pembeli' AND DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'")[0];
    $rekap_penjual = query("SELECT SUM(total_harga) as total FROM transaksi WHERE tipe='penjual' AND DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'")[0];
    
    $total_penjualan = $rekap_pembeli['total'] ?? 0;
    $total_pembelian = $rekap_penjual['total'] ?? 0;
    $total_transaksi = $rekap_pembeli['count'] ?? 0;
    $rekap_cabang = []; // skip per-branch rekap for old structure for brevity

    $transaksi_headers = query("
        SELECT
            no_invoice, cabang_id, nama_kasir, tipe,
            MIN(created_at) as created_at,
            SUM(total_harga) as total_harga,
            (SELECT nama_cabang FROM cabang WHERE id = t.cabang_id) as nama_cabang
        FROM transaksi t
        WHERE DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
        " . ($cabang_id > 0 ? " AND cabang_id = $cabang_id" : "") . "
        " . ($tipe_filter != 'semua' ? " AND tipe = '$tipe_filter'" : "") . "
        GROUP BY no_invoice, cabang_id, nama_kasir, tipe
        ORDER BY created_at DESC
    ");
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 30;
$offset = ($page - 1) * $limit;
$total_data = count($transaksi_headers);
$total_pages = ceil($total_data / $limit);
$transaksi_page = array_slice($transaksi_headers, $offset, $limit);
?>
<div class="p-6">
    <h1 class="judul text-3xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="mr-3">📊</span> LAPORAN TRANSAKSI KASIR
    </h1>

    <!-- Filter -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" class="flex flex-wrap md:flex-nowrap items-end gap-3">
            <div class="flex-1 min-w-[140px]">
                <label class="block text-gray-700 font-medium mb-1 text-xs uppercase tracking-wider">📅 Mulai</label>
                <input type="date" name="tanggal_mulai" value="<?= $tanggal_mulai ?>" class="w-full border rounded-lg p-2 text-sm">
            </div>
            <div class="flex-1 min-w-[140px]">
                <label class="block text-gray-700 font-medium mb-1 text-xs uppercase tracking-wider">📅 Akhir</label>
                <input type="date" name="tanggal_akhir" value="<?= $tanggal_akhir ?>" class="w-full border rounded-lg p-2 text-sm">
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-gray-700 font-medium mb-1 text-xs uppercase tracking-wider">🏢 Cabang</label>
                <select name="cabang" class="w-full border rounded-lg p-2 text-sm">
                    <option value="0">Semua</option>
                    <?php foreach ($cabang_list as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $cabang_id ? 'selected' : '' ?>><?= $c['nama_cabang'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-gray-700 font-medium mb-1 text-xs uppercase tracking-wider">📋 Tipe</label>
                <select name="tipe" class="w-full border rounded-lg p-2 text-sm">
                    <option value="semua" <?= $tipe_filter == 'semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="pembeli" <?= $tipe_filter == 'pembeli' ? 'selected' : '' ?>>Penjualan dari Pembeli</option>
                    <option value="penjual" <?= $tipe_filter == 'penjual' ? 'selected' : '' ?>>Penjualan dari Penjual</option>
                </select>
            </div>
            <div class="w-full md:w-auto">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg text-sm">🔍 CARI</button>
            </div>
        </form>
    </div>

    <!-- Summary Total Section -->
    <div class="bg-linear-to-r from-indigo-700 to-purple-900 text-black p-8 rounded-lg shadow-lg mb-6">
        <h2 class="text-2xl font-bold mb-6 flex items-center text-white">
            <span class="mr-3">📊</span> REKAP TRANSAKSI <?= date('d/m/Y', strtotime($tanggal_mulai)) ?> - <?= date('d/m/Y', strtotime($tanggal_akhir)) ?>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white bg-opacity-20 p-6 rounded-lg border border-white border-opacity-20">
                <p class="text-sm opacity-90 font-bold uppercase tracking-wider">💰 TOTAL PENJUALAN</p>
                <p class="text-5xl font-bold mt-2 font-mono"><?= rupiah($total_penjualan) ?></p>
            </div>
            <div class="bg-white bg-opacity-20 p-6 rounded-lg border border-white border-opacity-20">
                <p class="text-sm opacity-90 font-bold uppercase tracking-wider">📈 TOTAL TRANSAKSI</p>
                <p class="text-5xl font-bold mt-2 font-mono"><?= $total_transaksi ?></p>
            </div>
        </div>
    </div>

    <!-- Rekap per Cabang -->
    <?php if (!empty($rekap_cabang)): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <?php foreach ($rekap_cabang as $r): ?>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-indigo-600">
            <h3 class="font-bold text-gray-800 flex items-center mb-2">
                <span class="mr-2">🏢</span> <?= $r['nama'] ?>
            </h3>
            <div class="space-y-1">
                <div class="flex justify-between text-base">
                    <span class="text-gray-500 font-bold">TOTAL:</span>
                    <span class="font-bold text-indigo-700"><?= rupiah($r['penjualan']) ?></span>
                </div>
                <div class="flex justify-between text-xs text-gray-400 pl-2">
                    <span>Transaksi:</span>
                    <span><?= $r['transaksi'] ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gray-800 text-white p-4">
            <h2 class="text-xl font-bold">📋 DAFTAR TRANSAKSI</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Waktu</th>
                        <th class="p-3 text-left">Tipe</th>
                        <th class="p-3 text-left">Invoice</th>
                        <th class="p-3 text-left">Cabang</th>
                        <th class="p-3 text-left">Total</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transaksi_page)): ?>
                        <?php foreach ($transaksi_page as $t): ?>
                        <tr class="border-b hover:bg-gray-50 cursor-pointer" onclick="showTransactionDetail('<?= $t['no_invoice'] ?>')">
                            <td class="p-3 text-sm">
                                <?= date('d/m/y', strtotime($t['created_at'])) ?> 
                                <span class="text-gray-400"><?= date('H:i', strtotime($t['created_at'])) ?></span>
                            </td>
                            <td class="p-3">
                                <?php if ($t['tipe'] == 'pembeli'): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700">🛒 PEMBELI</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700">🤝 PENJUAL</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-mono text-sm font-bold text-blue-600"><?= $t['no_invoice'] ?></td>
                            <td class="p-3 text-sm"><?= $t['nama_cabang'] ?></td>
                            <td class="p-3 font-bold <?= $t['tipe'] == 'pembeli' ? 'text-indigo-700' : 'text-purple-700' ?>"><?= rupiah($t['total_harga']) ?></td>
                            <td class="p-3 text-center">
                                <button onclick="event.stopPropagation(); showTransactionDetail('<?= $t['no_invoice'] ?>')"
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs">📄 Detail</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="p-8 text-center text-gray-500">Tidak ada transaksi ditemukan</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="bg-gray-50 p-4 flex justify-center">
            <div class="flex space-x-1">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?tanggal_mulai=<?= $tanggal_mulai ?>&tanggal_akhir=<?= $tanggal_akhir ?>&cabang=<?= $cabang_id ?>&tipe=<?= $tipe_filter ?>&page=<?= $i ?>"
                   class="px-3 py-1 text-sm <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?> rounded-md"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Transaction Detail Modal -->
<div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 z-9999 hidden items-center justify-center">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-gray-800 text-white p-4 flex justify-between items-center">
            <h3 class="text-xl font-bold">📄 DETAIL TRANSAKSI</h3>
            <button onclick="closeDetailModal()" class="text-white hover:text-gray-300 text-2xl">&times;</button>
        </div>
        <div id="detailContent" class="p-6">
            <div class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
                <p class="mt-4">Loading...</p>
            </div>
        </div>
    </div>
</div>

<script>
function showTransactionDetail(invoice) {
    const modal = document.getElementById('detailModal');
    const content = document.getElementById('detailContent');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    // Fetch transaction detail via AJAX
    fetch('get_transaction_detail.php?invoice=' + invoice)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = '<p class="text-red-500 text-center">Error loading transaction detail</p>';
        });
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
    document.getElementById('detailModal').classList.remove('flex');
}

// Close modal on outside click
document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailModal();
    }
});

// Print struk
function printStruk() {
    window.print();
}
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #detailContent, #detailContent * {
        visibility: visible;
    }
    #detailContent {
        position: absolute;
        left: 0;
        top: 0;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<?php include '../../includes/layout_footer.php'; ?>
