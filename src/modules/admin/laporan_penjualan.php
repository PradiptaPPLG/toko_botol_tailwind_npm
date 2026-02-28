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

    // Rekap per cabang - Only include transactions not yet settled (sudah_disetor = false)
    $rekap_cabang = [];
    foreach ($cabang_list as $c) {
        $rekap = query("
            SELECT
                COUNT(*) as total_transaksi,
                SUM(total_harga) as total_penjualan
            FROM transaksi_header
            WHERE cabang_id = {$c['id']}
              AND DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
              AND (sudah_disetor = 0 OR sudah_disetor IS NULL)
        ")[0];

        $rekap_cabang[$c['id']] = [
            'nama' => $c['nama_cabang'],
            'transaksi' => $rekap['total_transaksi'] ?? 0,
            'penjualan' => $rekap['total_penjualan'] ?? 0
        ];
    }

    // Totals - Only unsettled
    $total_penjualan = array_sum(array_column($rekap_cabang, 'penjualan'));
    $total_transaksi = array_sum(array_column($rekap_cabang, 'transaksi'));

    // Get transaction headers - ALL transactions in report (settled and unsettled)
    $transaksi_headers = query("
        SELECT th.*, c.nama_cabang
        FROM transaksi_header th
        JOIN cabang c ON th.cabang_id = c.id
        WHERE DATE(th.created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
        " . ($cabang_id > 0 ? " AND th.cabang_id = $cabang_id" : "") . "
        ORDER BY th.created_at DESC
    ");

} else {
    // OLD STRUCTURE Fallback (Simplified as this is legacy)
    $rekap_pembeli = query("SELECT SUM(total_harga) as total, COUNT(DISTINCT no_invoice) as count FROM transaksi WHERE DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'")[0];
    
    $total_penjualan = $rekap_pembeli['total'] ?? 0;
    $total_transaksi = $rekap_pembeli['count'] ?? 0;
    $rekap_cabang = []; // skip per-branch rekap for old structure for brevity

    $transaksi_headers = query("
        SELECT
            no_invoice, cabang_id, nama_kasir,
            MIN(created_at) as created_at,
            SUM(total_harga) as total_harga,
            (SELECT nama_cabang FROM cabang WHERE id = t.cabang_id) as nama_cabang
        FROM transaksi t
        WHERE DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
        " . ($cabang_id > 0 ? " AND cabang_id = $cabang_id" : "") . "
        GROUP BY no_invoice, cabang_id, nama_kasir
        ORDER BY created_at DESC
    ");
}

// Get setoran history for current cabang
$setoran_list = [];
if ($use_new_structure) {
    $cabang_filter = $cabang_id > 0 ? " AND cabang_id = $cabang_id" : "";
    $setoran_list = query("
        SELECT * FROM setoran
        WHERE 1=1 $cabang_filter
        ORDER BY created_at DESC
        LIMIT 50
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
    <h1 class="judul text-fluid-3xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="mr-3">📊</span> LAPORAN TRANSAKSI KASIR
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
            <div class="flex-1 min-w-37.5">
                <label class="block text-gray-700 font-medium mb-1 text-fluid-xs uppercase tracking-wider">🏢 Cabang</label>
                <label>
                    <select name="cabang" class="w-full border rounded-lg p-2 text-fluid-sm" onchange="document.getElementById('filter-form').submit()">
                        <option value="0">Semua</option>
                        <?php foreach ($cabang_list as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $cabang_id ? 'selected' : '' ?>><?= $c['nama_cabang'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>



        </form>
    </div>

    <!-- Summary Total Section -->
    <div class="bg-linear-to-r from-indigo-700 to-purple-900 text-black p-8 rounded-lg shadow-lg mb-6">
        <div class="flex justify-between items-start mb-6">
            <h2 class="text-fluid-2xl font-bold flex items-center text-white">
                <span class="mr-3">📊</span> REKAP TRANSAKSI <?= date('d/m/Y', strtotime($tanggal_mulai)) ?> - <?= date('d/m/Y', strtotime($tanggal_akhir)) ?>
            </h2>
            <button onclick="showSetoranConfirmModal()" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg text-fluid-sm transition duration-200 flex items-center gap-2">
                💳 Setor
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white bg-opacity-20 p-6 rounded-lg border border-white border-opacity-20">
                <p class="text-fluid-sm opacity-90 font-bold uppercase tracking-wider">💰 TOTAL PENJUALAN</p>
                <p class="text-fluid-3xl font-bold mt-2 font-mono" id="total-penjualan-display" data-original="<?= rupiah($total_penjualan) ?>" data-amount="<?= $total_penjualan ?>"><?= rupiah($total_penjualan) ?></p>
            </div>
            <div class="bg-white bg-opacity-20 p-6 rounded-lg border border-white border-opacity-20">
                <p class="text-fluid-sm opacity-90 font-bold uppercase tracking-wider">📈 TOTAL TRANSAKSI</p>
                <p class="text-fluid-3xl font-bold mt-2 font-mono"><?= $total_transaksi ?></p>
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
                <div class="flex justify-between text-fluid-base">
                    <span class="text-gray-500 font-bold">TOTAL:</span>
                    <span class="font-bold text-indigo-700"><?= rupiah($r['penjualan']) ?></span>
                </div>
                <div class="flex justify-between text-fluid-xs text-gray-400 pl-2">
                    <span>Transaksi:</span>
                    <span><?= $r['transaksi'] ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Setoran History -->
    <?php if (!empty($setoran_list)): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="bg-blue-800 text-white p-4">
            <h2 class="text-fluid-xl font-bold">💳 RIWAYAT SETORAN</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="p-3 text-left">Tanggal Setor</th>
                        <th class="p-3 text-left">Periode</th>
                        <th class="p-3 text-left">Cabang</th>
                        <th class="p-3 text-right">Total Setor</th>
                        <th class="p-3 text-left">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($setoran_list as $s): ?>
                    <tr class="border-b hover:bg-blue-50">
                        <td class="p-3 text-fluid-sm">
                            <?= date('d/m/Y H:i', strtotime($s['created_at'])) ?>
                        </td>
                        <td class="p-3 text-fluid-sm">
                            <?= date('d/m/Y', strtotime($s['tanggal_dari'])) ?> - <?= date('d/m/Y', strtotime($s['tanggal_sampai'])) ?>
                        </td>
                        <td class="p-3 text-fluid-sm">
                            <?php 
                                if ($s['cabang_id'] == 0) {
                                    echo '<span class="font-bold text-blue-600">🏢 Semua Cabang</span>';
                                } else {
                                    $cabang_name = query("SELECT nama_cabang FROM cabang WHERE id = {$s['cabang_id']}");
                                    echo $cabang_name[0]['nama_cabang'] ?? 'N/A';
                                }
                            ?>
                        </td>
                        <td class="p-3 text-right font-bold text-blue-600">
                            <?= rupiah($s['total_setor']) ?>
                        </td>
                        <td class="p-3 text-fluid-sm text-gray-600">
                            <?= $s['keterangan'] ?? '-' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gray-800 text-white p-4">
            <h2 class="text-fluid-xl font-bold">📋 DAFTAR TRANSAKSI</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Waktu</th>
                        <th class="p-3 text-left">Invoice</th>
                        <th class="p-3 text-left">Cabang</th>
                        <th class="p-3 text-left">Total</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transaksi_page)): ?>
                        <?php foreach ($transaksi_page as $t): ?>
                        <tr class="border-b hover:bg-gray-50 cursor-pointer" onclick="showTransactionDetail('<?= $t['no_invoice'] ?>')">
                            <td class="p-3 text-fluid-sm">
                                <?= date('d/m/y', strtotime($t['created_at'])) ?> 
                                <span class="text-gray-400"><?= date('H:i', strtotime($t['created_at'])) ?></span>
                            </td>
                            <td class="p-3 font-mono text-fluid-sm font-bold text-blue-600"><?= $t['no_invoice'] ?></td>
                            <td class="p-3 text-fluid-sm"><?= $t['nama_cabang'] ?></td>
                            <td class="p-3 font-bold text-indigo-700"><?= rupiah($t['total_harga']) ?></td>
                            <td class="p-3 text-center">
                                <?php if ($t['sudah_disetor']): ?>
                                    <span class="inline-block px-3 py-1 rounded-full text-fluid-xs font-bold bg-green-100 text-green-800">✓ Sudah Disetor</span>
                                <?php else: ?>
                                    <span class="inline-block px-3 py-1 rounded-full text-fluid-xs font-bold bg-yellow-100 text-yellow-800">⏳ Belum Disetor</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-center">
                                <button onclick="event.stopPropagation(); showTransactionDetail('<?= $t['no_invoice'] ?>')"
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-fluid-xs">📄 Detail</button>
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
                <a href="?tanggal_mulai=<?= $tanggal_mulai ?>&tanggal_akhir=<?= $tanggal_akhir ?>&cabang=<?= $cabang_id ?>&page=<?= $i ?>"
                   class="px-3 py-1 text-fluid-sm <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?> rounded-md"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Transaction Detail Modal -->
<div id="detailModal" class="fixed inset-0 bg-transparent z-9999 hidden items-center justify-center">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-gray-800 text-white p-4 flex justify-between items-center">
            <h3 class="text-fluid-xl font-bold">📄 DETAIL TRANSAKSI</h3>
            <button onclick="closeDetailModal()" class="text-white hover:text-gray-300 text-fluid-2xl">&times;</button>
        </div>
        <div id="detailContent" class="p-6">
            <div class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
                <p class="mt-4">Loading...</p>
            </div>
        </div>
    </div>
</div>

<!-- Setor Confirmation Modal -->
<div id="setoranConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 z-9999 hidden items-center justify-center">
    <div class="bg-white rounded-lg shadow-2xl max-w-md w-full mx-4">
        <div class="bg-blue-600 text-white p-4 flex justify-between items-center">
            <h3 class="text-fluid-xl font-bold">💳 Konfirmasi Setoran</h3>
            <button onclick="closeSetoranConfirmModal()" class="text-white hover:text-gray-300 text-fluid-2xl">&times;</button>
        </div>
        <div class="p-6">
            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                <p class="text-gray-600 text-fluid-xs uppercase font-bold mb-1">Total Setoran</p>
                <p class="text-fluid-3xl font-bold text-blue-700" id="setoranAmount">Rp 0</p>
            </div>
            <p class="text-gray-800 text-fluid-sm mb-4">
                Apakah Anda yakin ingin melakukan setoran? <br><br>
                <strong class="text-blue-600">✓ Data transaksi dan penjualan tetap tersimpan</strong> di sistem dan akan tercatat dalam riwayat setoran.
            </p>
            <div class="space-y-3 mb-4">
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="setoranSemuaCabang" onchange="toggleSemuaCabang()" class="w-4 h-4 text-blue-600 rounded">
                    <label for="setoranSemuaCabang" class="text-gray-700 font-medium text-fluid-sm cursor-pointer">✓ Setor Semua Cabang</label>
                </div>
                <div id="cabangSelectDiv">
                    <label class="block text-gray-700 font-medium text-fluid-xs mb-1 uppercase">🏢 Pilih Cabang</label>
                    <select id="setoranCabang" onchange="updateSetoranTotal()" class="w-full border rounded-lg p-2 text-fluid-sm">
                        <option value="">-- Pilih Cabang --</option>
                        <?php foreach ($cabang_list as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-medium text-fluid-xs mb-1 uppercase">Keterangan (Opsional)</label>
                    <input type="text" id="setoranKeterangan" placeholder="Contoh: Setoran Harian" class="w-full border rounded-lg p-2 text-fluid-sm">
                </div>
            </div>
            <div class="flex gap-3 justify-end">
                <button onclick="closeSetoranConfirmModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg text-fluid-sm transition">
                    ❌ Batal
                </button>
                <button onclick="confirmSetoranPenjualan()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-fluid-sm transition">
                    ✓ Proses Setoran
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variable for total element
let totalElement = null;

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

// Show setor confirmation modal
function showSetoranConfirmModal() {
    totalElement = document.getElementById('total-penjualan-display');
    const amount = parseFloat(totalElement.getAttribute('data-amount')) || 0;
    
    if (amount <= 0) {
        showNotification('❌ Total penjualan harus lebih besar dari 0', 'error');
        return;
    }
    
    // Format and display amount in modal
    const formatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
    document.getElementById('setoranAmount').textContent = formatter.format(amount);
    
    const modal = document.getElementById('setoranConfirmModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Close setor confirmation modal
function closeSetoranConfirmModal() {
    document.getElementById('setoranConfirmModal').classList.add('hidden');
    document.getElementById('setoranConfirmModal').classList.remove('flex');
    document.getElementById('setoranSemuaCabang').checked = false;
    document.getElementById('setoranCabang').value = '';
    document.getElementById('setoranKeterangan').value = '';
    document.getElementById('cabangSelectDiv').style.display = 'block';
    document.getElementById('setoranAmount').textContent = 'Rp 0';
    document.getElementById('setoranAmount').removeAttribute('data-amount');
}

// Toggle Semua Cabang checkbox
function toggleSemuaCabang() {
    const checkbox = document.getElementById('setoranSemuaCabang');
    const cabangDiv = document.getElementById('cabangSelectDiv');
    
    if (checkbox.checked) {
        cabangDiv.style.display = 'none';
        document.getElementById('setoranCabang').value = '';
        updateSetoranTotal();
    } else {
        cabangDiv.style.display = 'block';
        document.getElementById('setoranAmount').textContent = 'Rp 0';
        document.getElementById('setoranAmount').removeAttribute('data-amount');
    }
}

// Update total setor based on selected cabang or Semua Cabang
function updateSetoranTotal() {
    const checkbox = document.getElementById('setoranSemuaCabang');
    const tanggalMulai = new URLSearchParams(window.location.search).get('tanggal_mulai') || new Date().toISOString().split('T')[0];
    const tanggalAkhir = new URLSearchParams(window.location.search).get('tanggal_akhir') || new Date().toISOString().split('T')[0];
    
    let cabangId;
    
    if (checkbox.checked) {
        // Semua Cabang - use 0 to indicate all branches
        cabangId = 0;
    } else {
        cabangId = document.getElementById('setoranCabang').value;
        if (!cabangId) {
            document.getElementById('setoranAmount').textContent = 'Rp 0';
            return;
        }
    }
    
    // Show loading state
    document.getElementById('setoranAmount').textContent = 'Loading...';
    
    // Fetch total for this cabang or all cabangs
    fetch('get_cabang_total.php?cabang_id=' + cabangId + '&tanggal_mulai=' + tanggalMulai + '&tanggal_akhir=' + tanggalAkhir)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const formatter = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                });
                const formattedAmount = formatter.format(data.total);
                document.getElementById('setoranAmount').textContent = formattedAmount;
                // Store the actual amount for submission
                document.getElementById('setoranAmount').setAttribute('data-amount', data.total);
            } else {
                document.getElementById('setoranAmount').textContent = 'Rp 0';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('setoranAmount').textContent = 'Rp 0';
        });
}

// Confirm and execute setor
function confirmSetoranPenjualan() {
    const amountElement = document.getElementById('setoranAmount');
    const amount = parseFloat(amountElement.getAttribute('data-amount')) || 0;
    const tanggalMulai = new URLSearchParams(window.location.search).get('tanggal_mulai') || new Date().toISOString().split('T')[0];
    const tanggalAkhir = new URLSearchParams(window.location.search).get('tanggal_akhir') || new Date().toISOString().split('T')[0];
    const checkbox = document.getElementById('setoranSemuaCabang');
    const keterangan = document.getElementById('setoranKeterangan').value;
    
    let cabangId;
    
    if (checkbox.checked) {
        cabangId = 0; // 0 means all cabangs
    } else {
        cabangId = document.getElementById('setoranCabang').value;
        if (!cabangId || cabangId === '') {
            showNotification('❌ Pilih cabang untuk melakukan setoran', 'error');
            return;
        }
    }
    
    if (amount <= 0) {
        showNotification('❌ Total penjualan harus lebih besar dari 0', 'error');
        return;
    }
    
    // Save setor to database via AJAX
    fetch('save_setoran.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `total_setor=${amount}&tanggal_dari=${tanggalMulai}&tanggal_sampai=${tanggalAkhir}&cabang_id=${cabangId}&keterangan=${encodeURIComponent(keterangan)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reset display
            totalElement.textContent = 'Rp 0';
            totalElement.setAttribute('data-amount', '0');
            totalElement.classList.add('text-red-300');
            
            closeSetoranConfirmModal();
            showNotification('✓ Setoran berhasil disimpan', 'success');
            
            // Reload setoran table after 1.5 seconds
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification('❌ Gagal menyimpan setoran: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('❌ Error: ' + error.message, 'error');
    });
}

// Notification function
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg text-white text-fluid-sm z-9999 ${type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Close modal on outside click
document.getElementById('setoranConfirmModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSetoranConfirmModal();
    }
});
</script>

<?php include '../../includes/layout_footer.php'; ?>
