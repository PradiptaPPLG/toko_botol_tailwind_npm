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

$cabang_list = get_cabang();

// Check if using new structure or old structure
$use_new_structure = false;
$check_new_table = query("SHOW TABLES LIKE 'transaksi_header'");
if (count($check_new_table) > 0) {
    $use_new_structure = true;
}

if ($use_new_structure) {
    // NEW STRUCTURE: Use transaksi_header (grouped by invoice)

    // Rekap penjualan per cabang
    $rekap_cabang = [];
    foreach ($cabang_list as $c) {
        $rekap = query("
            SELECT
                COUNT(*) as total_transaksi,
                SUM(total_harga) as total_penjualan
            FROM transaksi_header
            WHERE cabang_id = {$c['id']}
              AND tipe = 'pembeli'
              AND DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
        ")[0];

        $rekap_cabang[$c['id']] = [
            'nama' => $c['nama_cabang'],
            'transaksi' => $rekap['total_transaksi'] ?? 0,
            'penjualan' => $rekap['total_penjualan'] ?? 0
        ];
    }

    // Total semua cabang
    $total_penjualan = array_sum(array_column($rekap_cabang, 'penjualan'));
    $total_transaksi = array_sum(array_column($rekap_cabang, 'transaksi'));

    // Get transaction headers (grouped by invoice)
    $transaksi_headers = query("
        SELECT th.*, c.nama_cabang
        FROM transaksi_header th
        JOIN cabang c ON th.cabang_id = c.id
        WHERE th.tipe = 'pembeli'
          AND DATE(th.created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
        " . ($cabang_id > 0 ? " AND th.cabang_id = $cabang_id" : "") . "
        ORDER BY th.created_at DESC
    ");

} else {
    // OLD STRUCTURE: Fallback to old transaksi table

    $rekap_cabang = [];
    foreach ($cabang_list as $c) {
        $rekap = query("
            SELECT
                COUNT(DISTINCT no_invoice) as total_transaksi,
                SUM(total_harga) as total_penjualan
            FROM transaksi
            WHERE cabang_id = {$c['id']}
              AND tipe = 'pembeli'
              AND DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
        ")[0];

        $rekap_cabang[$c['id']] = [
            'nama' => $c['nama_cabang'],
            'transaksi' => $rekap['total_transaksi'] ?? 0,
            'penjualan' => $rekap['total_penjualan'] ?? 0
        ];
    }

    $total_penjualan = array_sum(array_column($rekap_cabang, 'penjualan'));
    $total_transaksi = array_sum(array_column($rekap_cabang, 'transaksi'));

    // Group old transactions by invoice
    $transaksi_headers = query("
        SELECT
            no_invoice,
            cabang_id,
            nama_kasir,
            MIN(created_at) as created_at,
            SUM(total_harga) as total_harga,
            COUNT(*) as total_items,
            (SELECT nama_cabang FROM cabang WHERE id = t.cabang_id) as nama_cabang
        FROM transaksi t
        WHERE tipe = 'pembeli'
          AND DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
        " . ($cabang_id > 0 ? " AND cabang_id = $cabang_id" : "") . "
        GROUP BY no_invoice, cabang_id, nama_kasir
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
        <span class="mr-3">💰</span> LAPORAN PENJUALAN
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
                <label class="block text-gray-700 font-medium mb-2">🏢 Cabang</label>
                <label>
                    <select name="cabang" class="w-full border rounded-lg p-3 text-lg">
                        <option value="0">Semua Cabang</option>
                        <?php foreach ($cabang_list as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $cabang_id ? 'selected' : '' ?>>
                            <?= $c['nama_cabang'] ?>
                        </option>
                        <?php endforeach; ?>
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

    <!-- Rekap Total Penjualan -->
    <div class="bg-linear-to-r from-green-700 to-green-900 text-black p-8 rounded-lg shadow-lg mb-6">
        <h2 class="text-2xl font-bold mb-6 flex items-center">
            <span class="mr-3">📊</span> REKAP PENJUALAN <?= date('d/m/Y', strtotime($tanggal_mulai)) ?> - <?= date('d/m/Y', strtotime($tanggal_akhir)) ?>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white bg-opacity-20 p-6 rounded-lg">
                <p class="text-sm opacity-90">💰 TOTAL PENJUALAN</p>
                <p class="text-5xl font-bold mt-2"><?= rupiah($total_penjualan) ?></p>
                <p class="text-xs mt-2 opacity-75">Dari pembeli</p>
            </div>
            <div class="bg-white bg-opacity-20 p-6 rounded-lg">
                <p class="text-sm opacity-90">📈 TOTAL TRANSAKSI</p>
                <p class="text-5xl font-bold mt-2"><?= $total_transaksi ?></p>
                <p class="text-xs mt-2 opacity-75">Invoice</p>
            </div>
        </div>
    </div>

    <!-- Rekap per Cabang -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <?php foreach ($rekap_cabang as $r): ?>
        <div class="bg-white rounded-lg shadow p-5 border-l-8 border-green-500">
            <h3 class="text-xl font-bold text-gray-800 mb-2">🏢 <?= $r['nama'] ?></h3>
            <div class="flex justify-between mt-2">
                <span>Transaksi:</span>
                <span class="font-bold"><?= $r['transaksi'] ?>x</span>
            </div>
            <div class="flex justify-between mt-1">
                <span>Penjualan:</span>
                <span class="font-bold text-green-600"><?= rupiah($r['penjualan']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Daftar Transaksi (Grouped by Invoice) -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gray-800 text-white p-4">
            <h2 class="text-xl font-bold">📋 DAFTAR TRANSAKSI (KLIK UNTUK DETAIL)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Waktu</th>
                        <th class="p-3 text-left">Invoice</th>
                        <th class="p-3 text-left">Cabang</th>
                        <th class="p-3 text-left">Kasir</th>
                        <th class="p-3 text-left">Total</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transaksi_page)): ?>
                        <?php foreach ($transaksi_page as $t): ?>
                        <tr class="border-b hover:bg-gray-50 cursor-pointer" onclick="showTransactionDetail('<?= $t['no_invoice'] ?>')">
                            <td class="p-3"><?= date('H:i:s', strtotime($t['created_at'])) ?></td>
                            <td class="p-3 font-mono text-sm font-bold text-blue-600"><?= $t['no_invoice'] ?></td>
                            <td class="p-3"><?= $t['nama_cabang'] ?></td>
                            <td class="p-3"><?= $t['nama_kasir'] ?></td>
                            <td class="p-3 font-bold text-green-600 text-lg"><?= rupiah($t['total_harga']) ?></td>
                            <td class="p-3 text-center">
                                <button onclick="event.stopPropagation(); showTransactionDetail('<?= $t['no_invoice'] ?>')"
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm">
                                    📄 Detail
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-500">
                                Belum ada transaksi penjualan
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
                <a href="?tanggal_mulai=<?= $tanggal_mulai ?>&tanggal_akhir=<?= $tanggal_akhir ?>&cabang=<?= $cabang_id ?>&page=<?= $i ?>"
                   class="px-4 py-2 <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?> rounded-lg">
                    <?= $i ?>
                </a>
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
