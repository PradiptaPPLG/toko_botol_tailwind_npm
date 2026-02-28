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



$pengeluaran = query("
    SELECT *
    FROM pengeluaran
    WHERE DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
    AND deleted_at IS NULL
    ORDER BY created_at DESC
");

$total_pengeluaran = array_sum(array_column($pengeluaran, 'nominal'));
?>
<div class="p-6">
    <h1 class="judul text-fluid-3xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="mr-3">💸</span> LAPORAN PENGELUARAN
    </h1>

    <!-- Filter -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" id="filter-form" class="flex flex-wrap md:flex-nowrap items-end gap-3">
            <div class="flex-1 min-w-35">
                <label class="block text-gray-700 font-medium mb-1 text-fluid-xs uppercase tracking-wider">📅 Mulai</label>
                <label>
                    <input type="date" name="tanggal_mulai" value="<?= $tanggal_mulai ?>" class="w-full border rounded-lg p-2 text-fluid-sm" onchange="document.getElementById('filter-form').submit()">
                </label>
            </div>
            <div class="flex-1 min-w-35">
                <label class="block text-gray-700 font-medium mb-1 text-fluid-xs uppercase tracking-wider">📅 Akhir</label>
                <label>
                    <input type="date" name="tanggal_akhir" value="<?= $tanggal_akhir ?>" class="w-full border rounded-lg p-2 text-fluid-sm" onchange="document.getElementById('filter-form').submit()">
                </label>
            </div>
            <div class="flex-1 min-w-35">
                <label class="block text-gray-700 font-medium mb-1 text-fluid-xs uppercase tracking-wider">🔎 Keterangan</label>
                <input type="text" id="filter-keterangan" oninput="filterKeterangan()" class="w-full border rounded-lg p-2 text-fluid-sm" placeholder="Cari keterangan...">
            </div>


        </form>
    </div>

    <!-- Summary Total Section -->
    <div class="bg-linear-to-r from-indigo-700 to-purple-900 text-black p-8 rounded-lg shadow-lg mb-6">
        <h2 class="text-fluid-2xl font-bold mb-6 flex items-center text-white">
            <span class="mr-3">📊</span> REKAP PENGELUARAN <?= date('d/m/Y', strtotime($tanggal_mulai)) ?> - <?= date('d/m/Y', strtotime($tanggal_akhir)) ?>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white bg-opacity-20 p-6 rounded-lg border border-white border-opacity-20">
                <p class="text-fluid-sm opacity-90 font-bold uppercase tracking-wider">💸 TOTAL PENGELUARAN</p>
                <p class="text-fluid-3xl font-bold mt-2 font-mono" data-total-pengeluaran><?= rupiah($total_pengeluaran) ?></p>
            </div>
            <div class="bg-white bg-opacity-20 p-6 rounded-lg border border-white border-opacity-20">
                <p class="text-fluid-sm opacity-90 font-bold uppercase tracking-wider">🧾 JUMLAH TRANSAKSI</p>
                <p class="text-fluid-3xl font-bold mt-2 font-mono" data-count-pengeluaran><?= count($pengeluaran) ?></p>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gray-800 text-white p-4">
            <h2 class="text-fluid-xl font-bold">📋 DAFTAR PENGELUARAN</h2>
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
                        <tr class="pengeluaran-row border-b hover:bg-gray-50" data-keterangan="<?= strtolower(htmlspecialchars($p['keterangan'])) ?>" data-nominal="<?= (float)$p['nominal'] ?>">
                            <td class="p-3 text-fluid-sm">
                                <?= date('d/m/y', strtotime($p['created_at'])) ?>
                                <span class="text-gray-400"><?= date('H:i', strtotime($p['created_at'])) ?></span>
                            </td>
                            <td class="p-3 text-fluid-sm text-gray-800">
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

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-6 rounded-lg text-fluid-sm text-blue-900">
        <h3 class="font-bold mb-1">📌 Catatan:</h3>
        <p>Laporan ini mengambil data langsung dari tabel <span class="font-mono font-semibold">pengeluaran</span> yang dicatat melalui menu <span class="font-semibold">Pengeluaran</span>.</p>
    </div>
</div>

<script>
function filterKeterangan() {
    const term = document.getElementById('filter-keterangan').value.toLowerCase();
    let totalPengeluaran = 0;
    let visibleCount = 0;
    
    document.querySelectorAll('.pengeluaran-row').forEach(row => {
        const isVisible = row.getAttribute('data-keterangan').includes(term);
        row.style.display = isVisible ? '' : 'none';
        
        if (isVisible) {
            totalPengeluaran += parseFloat(row.getAttribute('data-nominal'));
            visibleCount++;
        }
    });
    
    // Update total display
    updateTotalPengeluaran(totalPengeluaran, visibleCount);
}

function updateTotalPengeluaran(total, count) {
    // Find and update the total in the summary section
    const totalElement = document.querySelector('[data-total-pengeluaran]');
    const countElement = document.querySelector('[data-count-pengeluaran]');
    
    if (totalElement) {
        totalElement.textContent = formatRupiah(total);
    }
    if (countElement) {
        countElement.textContent = count;
    }
}

function formatRupiah(value) {
    const formatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
    return formatter.format(value);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Store original totals as data attributes for reset
    const totalPengeluaranElement = document.querySelector('[data-total-pengeluaran]');
    const countPengeluaranElement = document.querySelector('[data-count-pengeluaran]');
    
    if (totalPengeluaranElement && countPengeluaranElement) {
        let totalPengeluaran = 0;
        let count = 0;
        document.querySelectorAll('.pengeluaran-row').forEach(row => {
            totalPengeluaran += parseFloat(row.getAttribute('data-nominal'));
            count++;
        });
        
        // Store original data
        totalPengeluaranElement.dataset.originalTotal = totalPengeluaran;
        countPengeluaranElement.dataset.originalCount = count;
    }
});
</script>

<?php include '../../includes/layout_footer.php'; ?>

