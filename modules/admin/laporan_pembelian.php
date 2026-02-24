<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
if (!is_login() || !is_admin()) redirect('../../login.php');

$title = 'Laporan Stok';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';
include '../../includes/modal_confirm.php';

$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-d');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$tipe_filter = $_GET['tipe'] ?? 'semua'; // semua, masuk, rusak, transfer

$cabang_list = get_cabang();

// Get stok masuk — grouped by batch_id
$stok_masuk_raw = query("
    SELECT
        sm.id,
        sm.batch_id,
        sm.created_at,
        'Stok Masuk' as tipe,
        '📦' as icon,
        p.nama_produk,
        sm.jumlah,
        sm.harga_beli_satuan,
        sm.total_belanja,
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

// Group stok_masuk by batch_id for display
$stok_masuk_batches = [];
$stok_masuk_detail = []; // batch_id => array of items
foreach ($stok_masuk_raw as $row) {
    $bid = $row['batch_id'] ?: $row['id'];
    if (!isset($stok_masuk_batches[$bid])) {
        $stok_masuk_batches[$bid] = [
            'batch_id'    => $bid,
            'created_at'  => $row['created_at'],
            'keterangan'  => $row['keterangan'],
            'tipe'        => $row['tipe'],
            'icon'        => $row['icon'],
            'badge_color' => $row['badge_color'],
            'total_jumlah'=> 0,
            'total_belanja'=> 0,
            'produk_count'=> 0,
        ];
    }
    $subtotal = (float)($row['total_belanja'] ?? ($row['jumlah'] * $row['harga_beli_satuan']));
    $stok_masuk_batches[$bid]['total_jumlah']  += (float)$row['jumlah'];
    $stok_masuk_batches[$bid]['total_belanja'] += $subtotal;
    $stok_masuk_batches[$bid]['produk_count']  += 1;
    $stok_masuk_detail[$bid][] = $row;
}
$stok_masuk = array_values($stok_masuk_batches);

// Get stok rusak
$stok_rusak_raw = query("
    SELECT
        sk.id,
        sk.batch_id,
        sk.created_at,
        'Stok Rusak' as tipe,
        '❌' as icon,
        p.nama_produk,
        sk.jumlah,
        sk.keterangan,
        'bg-red-100 text-red-800' as badge_color
    FROM stok_keluar sk
    JOIN produk p ON sk.produk_id = p.id
    WHERE sk.kondisi = 'rusak'
      AND DATE(sk.created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
    " . ($tipe_filter == 'rusak' ? "" : ($tipe_filter == 'semua' ? "" : "AND 1=0")) . "
    ORDER BY sk.created_at DESC
");

// Group stok_rusak by batch_id
$stok_rusak_batches = [];
foreach ($stok_rusak_raw as $row) {
    $bid = $row['batch_id'] ?: 'R-'.$row['id'];
    if (!isset($stok_rusak_batches[$bid])) {
        $stok_rusak_batches[$bid] = [
            'batch_id'    => $bid,
            'created_at'  => $row['created_at'],
            'keterangan'  => $row['keterangan'],
            'tipe'        => $row['tipe'],
            'icon'        => $row['icon'],
            'badge_color' => $row['badge_color'],
            'total_jumlah'=> 0,
            'produk_count'=> 0,
        ];
    }
    $stok_rusak_batches[$bid]['total_jumlah']  += (float)$row['jumlah'];
    $stok_rusak_batches[$bid]['produk_count']  += 1;
    $stok_masuk_detail[$bid][] = $row; // Reuse the detail array
}
$stok_rusak = array_values($stok_rusak_batches);

// Get stok transfer
$stok_transfer_raw = query("
    SELECT
        sk.id,
        sk.batch_id,
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

// Group stok_transfer by batch_id
$stok_transfer_batches = [];
foreach ($stok_transfer_raw as $row) {
    $bid = $row['batch_id'] ?: 'T-'.$row['id'];
    if (!isset($stok_transfer_batches[$bid])) {
        $stok_transfer_batches[$bid] = [
            'batch_id'    => $bid,
            'created_at'  => $row['created_at'],
            'keterangan'  => $row['keterangan'],
            'tipe'        => $row['tipe'],
            'icon'        => $row['icon'],
            'badge_color' => $row['badge_color'],
            'total_jumlah'=> 0,
            'produk_count'=> 0,
            'cabang_tujuan_nama' => $row['cabang_tujuan_nama']
        ];
    }
    $stok_transfer_batches[$bid]['total_jumlah']  += (float)$row['jumlah'];
    $stok_transfer_batches[$bid]['produk_count']  += 1;
    $stok_masuk_detail[$bid][] = $row; // Reuse the detail array
}
$stok_transfer = array_values($stok_transfer_batches);

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
$total_masuk = count($stok_masuk_batches);
$total_rusak = count($stok_rusak_batches);
$total_transfer = count($stok_transfer_batches);
$jumlah_masuk = array_sum(array_column($stok_masuk, 'total_jumlah'));
$jumlah_rusak = array_sum(array_column($stok_rusak, 'total_jumlah'));
$jumlah_transfer = array_sum(array_column($stok_transfer, 'total_jumlah'));
$total_belanja_masuk = array_sum(array_column($stok_masuk, 'total_belanja'));
// Encode detail for JS
$batch_detail_js = json_encode($stok_masuk_detail, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

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
                <label class="block text-gray-700 font-medium mb-1 text-xs uppercase tracking-wider">📋 Tipe</label>
                <select name="tipe" class="w-full border rounded-lg p-2 text-sm">
                    <option value="semua" <?= $tipe_filter == 'semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="masuk" <?= $tipe_filter == 'masuk' ? 'selected' : '' ?>>Stok Masuk</option>
                    <option value="rusak" <?= $tipe_filter == 'rusak' ? 'selected' : '' ?>>Stok Rusak</option>
                    <option value="transfer" <?= $tipe_filter == 'transfer' ? 'selected' : '' ?>>Transfer Cabang</option>
                </select>
            </div>
            <div class="w-full md:w-auto">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg text-sm">🔍 CARI</button>
            </div>
        </form>
    </div>

    <!-- Rekap Total -->
    <div class="bg-linear-to-r from-indigo-700 to-purple-900 text-black p-8 rounded-lg shadow-lg mb-6">
        <h2 class="text-2xl font-bold mb-6 flex items-center text-white">
            <span class="mr-3">📊</span> REKAP PERGERAKAN STOK <?= date('d/m/Y', strtotime($tanggal_mulai)) ?> - <?= date('d/m/Y', strtotime($tanggal_akhir)) ?>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white bg-opacity-20 p-6 rounded-lg border border-white border-opacity-20">
                <p class="text-sm opacity-90 font-bold uppercase tracking-wider">📦 STOK MASUK</p>
                <p class="text-5xl font-bold mt-2 font-mono"><?= $total_masuk ?></p>
                <p class="text-xs mt-2 opacity-75"><?= number_format($jumlah_masuk, 0, ',', '.') ?> botol</p>
            </div>
            <div class="bg-white bg-opacity-20 p-6 rounded-lg border border-white border-opacity-20">
                <p class="text-sm opacity-90 font-bold uppercase tracking-wider">💰 TOTAL BELANJA</p>
                <p class="text-3xl font-bold mt-2 font-mono"><?= rupiah($total_belanja_masuk) ?></p>
                <p class="text-xs mt-2 opacity-75">Dari stok masuk</p>
            </div>
            <div class="bg-white bg-opacity-20 p-6 rounded-lg border border-white border-opacity-20">
                <p class="text-sm opacity-90 font-bold uppercase tracking-wider">❌ STOK RUSAK</p>
                <p class="text-5xl font-bold mt-2 font-mono"><?= $total_rusak ?></p>
                <p class="text-xs mt-2 opacity-75"><?= number_format($jumlah_rusak, 0, ',', '.') ?> botol</p>
            </div>
            <div class="bg-white bg-opacity-20 p-6 rounded-lg border border-white border-opacity-20">
                <p class="text-sm opacity-90 font-bold uppercase tracking-wider">🚚 TRANSFER</p>
                <p class="text-5xl font-bold mt-2 font-mono"><?= $total_transfer ?></p>
                <p class="text-xs mt-2 opacity-75"><?= number_format($jumlah_transfer, 0, ',', '.') ?> botol</p>
            </div>
        </div>
    </div>

    <!-- Riwayat Pergerakan Stok -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gray-800 text-white p-4">
            <h2 class="text-xl font-bold">📋 RIWAYAT PERGERAKAN STOK (KLIK BARIS UNTUK DETAIL)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Waktu</th>
                        <th class="p-3 text-left">Tipe</th>
                        <th class="p-3 text-left">Produk / Batch</th>
                        <th class="p-3 text-left">Jumlah</th>
                        <th class="p-3 text-left">Total Belanja</th>
                        <th class="p-3 text-left">Keterangan</th>
                        <th class="p-3 text-left">Tujuan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($movements_page)): ?>
                        <?php foreach ($movements_page as $m): ?>
                        <tr class="border-b hover:bg-gray-50 cursor-pointer"
                            onclick="showBatchDetail('<?= $m['batch_id'] ?>')">
                            <td class="p-3">
                                <?= date('d/m/Y H:i', strtotime($m['created_at'])) ?>
                            </td>
                            <td class="p-3">
                                <span class="px-3 py-1 rounded-full text-xs font-bold <?= $m['badge_color'] ?>">
                                    <?= $m['icon'] ?> <?= $m['tipe'] ?>
                                </span>
                            </td>
                            <td class="p-3 font-semibold">
                                <span class="font-mono text-[10px] text-indigo-600"><?= $m['batch_id'] ?></span>
                                <span class="text-xs text-gray-500 ml-1">(<?= $m['produk_count'] ?> produk)</span>
                            </td>
                            <td class="p-3">
                                <span class="font-bold text-lg"><?= number_format($m['total_jumlah'], 0, ',', '.') ?></span>
                                <span class="text-sm text-gray-600">botol</span>
                            </td>
                            <td class="p-3">
                                <?php if (isset($m['total_belanja']) && $m['total_belanja'] > 0): ?>
                                    <span class="font-bold text-green-700"><?= rupiah($m['total_belanja']) ?></span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-sm text-gray-700">
                                <?= $m['keterangan'] ?? '-' ?>
                            </td>
                            <td class="p-3">
                                <?php if (!empty($m['cabang_tujuan_nama'])): ?>
                                    <span class="text-sm font-semibold text-blue-600">
                                        🏢 <?= $m['cabang_tujuan_nama'] ?>
                                    </span>
                                <?php else: ?>
                                    <button onclick="event.stopPropagation(); showBatchDetail('<?= $m['batch_id'] ?>')"
                                            class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1 rounded text-xs">
                                        📄 Detail
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-500">
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
            <li><strong>📦 Stok Masuk:</strong> Barang yang masuk ke gudang dari supplier (klik untuk detail)</li>
            <li><strong>❌ Stok Rusak:</strong> Barang yang rusak atau tidak layak jual</li>
            <li><strong>🚚 Transfer:</strong> Barang yang dipindahkan antar cabang</li>
        </ul>
    </div>
</div>

<!-- Batch Detail Modal -->
<div id="batchDetailModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div id="modalHeader" class="sticky top-0 bg-indigo-800 text-white p-4 flex justify-between items-center">
            <h3 id="modalTitle" class="text-xl font-bold">📦 DETAIL STOK MASUK</h3>
            <button onclick="closeBatchDetail()" class="text-white hover:text-gray-300 text-2xl">&times;</button>
        </div>
        <div id="batchDetailContent" class="p-6">
            <p class="text-center text-gray-400">Memuat...</p>
        </div>
    </div>
</div>

<script>
const batchDetail = <?= $batch_detail_js ?>;

function showBatchDetail(batchId) {
    const modal = document.getElementById('batchDetailModal');
    const content = document.getElementById('batchDetailContent');
    const items = batchDetail[batchId] || [];

    if (!items.length) {
        content.innerHTML = '<p class="text-center text-gray-400">Tidak ada data</p>';
    } else {
        const type = items[0].tipe;
        let totalVal = 0;
        let totalBotol = 0;
        
        // Update modal styling based on type
        const header = document.getElementById('modalHeader');
        const title = document.getElementById('modalTitle');
        if (type === 'Stok Masuk') {
            header.className = 'sticky top-0 bg-green-700 text-white p-4 flex justify-between items-center';
            title.textContent = '📦 DETAIL STOK MASUK';
        } else if (type === 'Stok Rusak') {
            header.className = 'sticky top-0 bg-red-700 text-white p-4 flex justify-between items-center';
            title.textContent = '❌ DETAIL STOK RUSAK';
        } else {
            header.className = 'sticky top-0 bg-blue-700 text-white p-4 flex justify-between items-center';
            title.textContent = '🚚 DETAIL TRANSFER STOK';
        }

        let html = `
            <div class="flex justify-between items-end mb-4 border-b pb-2">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Batch/Ref ID</p>
                    <p class="font-mono font-bold text-indigo-600">${batchId}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Tanggal</p>
                    <p class="text-sm">${new Date(items[0].created_at).toLocaleString('id-ID')}</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4 bg-gray-50 p-2 rounded"><strong>Keterangan:</strong> ${items[0].keterangan || '-'}</p>
            ${items[0].cabang_tujuan_nama ? `<p class="text-sm text-blue-600 mb-4 font-bold">🏢 Tujuan: ${items[0].cabang_tujuan_nama}</p>` : ''}
            <table class="w-full text-sm mb-4">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 text-left">Produk</th>
                        <th class="p-2 text-right">Jumlah</th>
                        ${type === 'Stok Masuk' ? `
                        <th class="p-2 text-right">Harga Beli/Satuan</th>
                        <th class="p-2 text-right">Subtotal</th>
                        ` : ''}
                    </tr>
                </thead>
                <tbody>`;
        items.forEach(item => {
            const subtotal = (parseFloat(item.total_belanja)) || (parseFloat(item.jumlah) * (parseFloat(item.harga_beli_satuan) || 0));
            totalVal += subtotal;
            totalBotol += parseFloat(item.jumlah);
            html += `
                <tr class="border-b">
                    <td class="p-2 font-semibold">${item.nama_produk}</td>
                    <td class="p-2 text-right">${parseInt(item.jumlah).toLocaleString('id-ID')} botol</td>
                    ${type === 'Stok Masuk' ? `
                    <td class="p-2 text-right">${formatRupiah(item.harga_beli_satuan)}</td>
                    <td class="p-2 text-right font-bold text-green-700">${formatRupiah(subtotal)}</td>
                    ` : ''}
                </tr>`;
        });
        html += `</tbody>
            </table>
            <div class="bg-gray-100 rounded-lg p-4 flex justify-between items-center">
                <div>
                    <span class="text-sm text-gray-600 tracking-wider font-bold">TOTAL ITEM: </span>
                    <span class="text-lg font-bold">${parseInt(totalBotol).toLocaleString('id-ID')} botol</span>
                </div>
                ${type === 'Stok Masuk' ? `
                <div>
                    <span class="text-sm text-gray-600 tracking-wider font-bold">TOTAL BELANJA: </span>
                    <span class="text-xl font-bold text-indigo-700">${formatRupiah(totalVal)}</span>
                </div>
                ` : ''}
            </div>`;
        content.innerHTML = html;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeBatchDetail() {
    document.getElementById('batchDetailModal').classList.add('hidden');
    document.getElementById('batchDetailModal').classList.remove('flex');
}

document.getElementById('batchDetailModal').addEventListener('click', function(e) {
    if (e.target === this) closeBatchDetail();
});

function formatRupiah(n) {
    return 'Rp ' + parseFloat(n || 0).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

<?php include '../../includes/layout_footer.php'; ?>
