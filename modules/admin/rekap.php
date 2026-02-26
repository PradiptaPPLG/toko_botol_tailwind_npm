<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
if (!is_login() || !is_admin()) redirect('../../login.php');

$title = 'Rekap Keuntungan';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';
include '../../includes/modal_confirm.php';

$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01'); // Default to start of current month
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

// ====== FETCH ALL BRANCHES ======
$cabang_list = query("SELECT * FROM cabang ORDER BY id");

// ====== PER-PRODUCT PER-BRANCH SALES DATA ======
$sales_data = query("
    SELECT 
        td.produk_id,
        td.nama_produk,
        p.harga_beli,
        th.cabang_id,
        SUM(td.jumlah) as total_qty,
        SUM(td.subtotal) as total_omzet
    FROM transaksi_detail td
    JOIN transaksi_header th ON td.transaksi_header_id = th.id
    LEFT JOIN produk p ON td.produk_id = p.id
    WHERE DATE(th.created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
    GROUP BY td.produk_id, td.nama_produk, p.harga_beli, th.cabang_id
    ORDER BY td.nama_produk ASC
");

// ====== PIVOT DATA: Build per-product summary ======
$products = []; // [produk_id => ['nama' => ..., 'harga_beli' => ..., 'branches' => [cabang_id => qty], 'omzet' => ...]]

foreach ($sales_data as $row) {
    $pid = $row['produk_id'];
    if (!isset($products[$pid])) {
        $products[$pid] = [
            'nama' => $row['nama_produk'],
            'harga_beli' => $row['harga_beli'] ?? 0,
            'branches' => [],
            'total_qty' => 0,
            'omzet' => 0,
        ];
    }
    $cid = $row['cabang_id'];
    $products[$pid]['branches'][$cid] = ($products[$pid]['branches'][$cid] ?? 0) + $row['total_qty'];
    $products[$pid]['total_qty'] += $row['total_qty'];
    $products[$pid]['omzet'] += $row['total_omzet'];
}

// Compute modal and profit for each product
foreach ($products as &$prod) {
    $prod['modal'] = $prod['total_qty'] * $prod['harga_beli'];
    $prod['profit'] = $prod['omzet'] - $prod['modal'];
}
unset($prod);

// ====== GRAND TOTALS ======
$grand_branch_qty = []; // [cabang_id => total_qty]
$grand_total_qty = 0;
$grand_modal = 0;
$grand_omzet = 0;
$grand_profit = 0;

foreach ($products as $prod) {
    foreach ($cabang_list as $cab) {
        $cid = $cab['id'];
        $grand_branch_qty[$cid] = ($grand_branch_qty[$cid] ?? 0) + ($prod['branches'][$cid] ?? 0);
    }
    $grand_total_qty += $prod['total_qty'];
    $grand_modal += $prod['modal'];
    $grand_omzet += $prod['omzet'];
    $grand_profit += $prod['profit'];
}

// ====== EXISTING: Pengeluaran & Kerugian (keep for bottom section) ======
$pengeluaran = query("
    SELECT SUM(nominal) as total 
    FROM pengeluaran 
    WHERE DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
")[0]['total'] ?? 0;

$kerugian_detail = query("
    SELECT * FROM (
        SELECT 
            'HILANG (SO)' as tipe,
            p.nama_produk,
            so.selisih as jumlah,
            0 as nominal,
            so.tanggal as tgl
        FROM stock_opname so
        JOIN produk p ON so.produk_id = p.id
        WHERE so.status = 'HILANG' AND so.is_cancelled = 0
          AND so.tanggal BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
        
        UNION ALL
        
        SELECT 
            'RUSAK' as tipe,
            p.nama_produk,
            sk.jumlah,
            0 as nominal,
            sk.created_at as tgl
        FROM stok_keluar sk
        JOIN produk p ON sk.produk_id = p.id
        WHERE sk.kondisi = 'rusak'
          AND DATE(sk.created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
    ) as losses
    ORDER BY tgl DESC
");

$total_kerugian = array_sum(array_column($kerugian_detail, 'nominal'));

// Net profit
$keuntungan_bersih = $grand_profit - $pengeluaran - $total_kerugian;
$is_rugi = $keuntungan_bersih < 0;
?>

<div class="p-4 lg:p-6">
    <h1 class="judul text-2xl lg:text-3xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="mr-3">📊</span> REKAP KEUNTUNGAN
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
            <div class="w-full md:w-auto">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg text-sm transition-all shadow-md">
                    🔍 CARI
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="bg-linear-to-r from-indigo-700 to-purple-900 text-black p-8 rounded-lg shadow-lg mb-6">
        <h2 class="text-xl font-bold mb-6 flex items-center text-white">
            <span class="mr-3">📈</span> REKAP PERIODE: <?= date('d/m/Y', strtotime($tanggal_mulai)) ?> - <?= date('d/m/Y', strtotime($tanggal_akhir)) ?>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white bg-opacity-10 p-5 rounded-lg border border-white border-opacity-20 hover:bg-opacity-20 transition-all">
                <p class="text-xs opacity-90 font-bold uppercase tracking-wider">💰 PENJUALAN (OMZET)</p>
                <p class="text-2xl font-bold mt-2 font-mono"><?= rupiah($grand_omzet) ?></p>
                <p class="text-[10px] mt-1 opacity-75">Total Pendapatan Penjualan</p>
            </div>
            <div class="bg-white bg-opacity-10 p-5 rounded-lg border border-white border-opacity-20 hover:bg-opacity-20 transition-all">
                <p class="text-xs opacity-90 font-bold uppercase tracking-wider">📦 MODAL (CAPITAL)</p>
                <p class="text-2xl font-bold mt-2 font-mono"><?= rupiah($grand_modal) ?></p>
                <p class="text-[10px] mt-1 opacity-75">Total Qty × Harga Beli</p>
            </div>
            <div class="bg-white bg-opacity-10 p-5 rounded-lg border border-white border-opacity-20 hover:bg-opacity-20 transition-all">
                <p class="text-xs opacity-90 font-bold uppercase tracking-wider">⚡ PENGELUARAN OPS</p>
                <p class="text-2xl font-bold mt-2 font-mono"><?= rupiah($pengeluaran) ?></p>
                <p class="text-[10px] mt-1 opacity-75">Biaya Operasional</p>
            </div>
            <div class="bg-white bg-opacity-10 p-5 rounded-lg border border-white border-opacity-20 hover:bg-opacity-20 transition-all">
                <p class="text-xs opacity-90 font-bold uppercase tracking-wider">❌ KERUGIAN</p>
                <p class="text-2xl font-bold mt-2 font-mono"><?= rupiah($total_kerugian) ?></p>
                <p class="text-[10px] mt-1 opacity-75">Rusak & Hilang</p>
            </div>
        </div>
    </div>
    <!-- ====== DETAIL PER-PRODUCT PER-BRANCH TABLE ====== -->
    <div class="rounded-lg shadow-md overflow-hidden mb-6">
        <div class="bg-gray-800 text-white p-4">
            <h3 class="text-lg font-bold flex items-center">
                <span class="mr-2">📋</span> DETAIL PENJUALAN PER PRODUK PER CABANG
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-600 font-bold">
                    <tr>
                        <th class="p-3 text-center" rowspan="2">No</th>
                        <th class="p-3 text-left" rowspan="2">Produk</th>
                        <th class="p-3 text-right" rowspan="2">Harga Beli</th>
                        <th class="p-3 text-center bg-blue-50" colspan="<?= count($cabang_list) ?>">Qty Terjual per Cabang</th>
                        <th class="p-3 text-center bg-yellow-50" rowspan="2">Total Qty</th>
                        <th class="p-3 text-center bg-orange-50" rowspan="2">Modal</th>
                        <th class="p-3 text-center bg-green-50" rowspan="2">Penjualan (Omzet)</th>
                        <th class="p-3 text-center bg-emerald-50" rowspan="2">Keuntungan</th>
                    </tr>
                    <tr>
                        <?php foreach ($cabang_list as $cab): ?>
                            <th class="p-3 text-center bg-blue-50 text-xs"><?= htmlspecialchars($cab['nama_cabang']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($products)): ?>
                        <?php $no = 1; foreach ($products as $pid => $prod): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-3 text-center text-gray-500"><?= $no++ ?></td>
                            <td class="p-3 font-semibold text-gray-800"><?= htmlspecialchars($prod['nama']) ?></td>
                            <td class="p-3 text-right font-mono text-gray-600"><?= rupiah($prod['harga_beli']) ?></td>
                            <?php foreach ($cabang_list as $cab): ?>
                                <?php $qty = $prod['branches'][$cab['id']] ?? 0; ?>
                                <td class="p-3 text-center font-mono <?= $qty > 0 ? 'text-blue-700 font-bold' : 'text-gray-300' ?>">
                                    <?= number_format($qty, 0, ',', '.') ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="p-3 text-center font-bold font-mono bg-yellow-50 text-yellow-800"><?= number_format($prod['total_qty'], 0, ',', '.') ?></td>
                            <td class="p-3 text-right font-mono bg-orange-50 text-orange-700"><?= rupiah($prod['modal']) ?></td>
                            <td class="p-3 text-right font-mono bg-green-50 text-green-700"><?= rupiah($prod['omzet']) ?></td>
                            <td class="p-3 text-right font-bold font-mono <?= $prod['profit'] >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?>">
                                <?= rupiah($prod['profit']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <!-- TOTAL ROW (product sums) -->
                        <tr class="bg-gray-700 text-white">
                            <td class="p-3 text-center" colspan="3">TOTAL</td>
                            <?php foreach ($cabang_list as $cab): ?>
                                <td class="p-3 text-center font-mono"><?= number_format($grand_branch_qty[$cab['id']] ?? 0, 0, ',', '.') ?></td>
                            <?php endforeach; ?>
                            <td class="p-3 text-center font-mono text-yellow-300"><?= number_format($grand_total_qty, 0, ',', '.') ?></td>
                            <td class="p-3 text-right font-mono text-orange-300"><?= rupiah($grand_modal) ?></td>
                            <td class="p-3 text-right font-mono text-green-300"><?= rupiah($grand_omzet) ?></td>
                            <td class="p-3 text-right font-mono <?= $grand_profit >= 0 ? 'text-emerald-300' : 'text-red-300' ?>"><?= rupiah($grand_profit) ?></td>
                        </tr>
                        <!-- PENGELUARAN ROW -->
                        <tr class="bg-gray-600 text-white">
                            <td class="p-3 text-center" colspan="<?= 3 + count($cabang_list) + 3 ?>">PENGELUARAN OPERASIONAL</td>
                            <td class="p-3 text-right font-mono">
                                <span class="text-red-300">- <?= rupiah($pengeluaran) ?></span>
                            </td>
                        </tr>
                        <!-- GRAND TOTAL ROW (total profit - pengeluaran) -->
                        <?php $final_total = $grand_profit - $pengeluaran; ?>
                        <tr class="bg-gray-900 text-white text-base">
                            <td class="p-3 text-center" colspan="<?= 3 + count($cabang_list) + 3 ?>">GRAND TOTAL (Total - Pengeluaran)</td>
                            <td class="p-3 text-right font-mono">
                                <span class="<?= $final_total >= 0 ? 'text-emerald-300' : 'text-red-300' ?> text-lg"><?= rupiah($final_total) ?></span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= 5 + count($cabang_list) ?>" class="p-8 text-center text-gray-400 italic">
                                Tidak ada data penjualan pada periode ini
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Final Profit Status Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Keuntungan Bersih Card -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-8 h-full flex flex-col items-center justify-center text-center border-t-8 <?= $is_rugi ? 'border-red-600' : 'border-green-600' ?>">
                <div class="w-20 h-20 bg-<?= $is_rugi ? 'red' : 'green' ?>-100 rounded-full flex items-center justify-center mb-6 shadow-sm border border-<?= $is_rugi ? 'red' : 'green' ?>-200">
                    <span class="text-4xl"><?= $is_rugi ? '📉' : '🚀' ?></span>
                </div>
                <h3 class="text-gray-500 font-bold uppercase tracking-[0.2em] text-[10px] mb-2">KEUNTUNGAN BERSIH</h3>
                <p class="text-4xl font-black mb-2 <?= $is_rugi ? 'text-red-700' : 'text-green-700' ?> font-mono">
                    <?= rupiah($keuntungan_bersih) ?>
                </p>
                <div class="px-6 py-2 rounded-full font-black text-xs <?= $is_rugi ? 'bg-red-600 text-white animate-pulse' : 'bg-green-600 text-white' ?> shadow-lg mb-4">
                    <?= $is_rugi ? '⚠️ RUGI' : '✅ PROFIT' ?>
                </div>
                <p class="text-[10px] text-gray-400 italic">
                    *Profit Produk - Operasional - Kerugian
                </p>
            </div>
        </div>

        <!-- Detail Kerugian Table -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-gray-800 text-white p-4">
                    <h3 class="text-lg font-bold flex items-center">
                        <span class="mr-2">📋</span> RINGKASAN KERUGIAN FISIK
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-gray-600 font-bold">
                            <tr>
                                <th class="p-3 text-left">Waktu</th>
                                <th class="p-3 text-left">Produk</th>
                                <th class="p-3 text-left">Kategori</th>
                                <th class="p-3 text-right">Qty</th>
                                <th class="p-3 text-right">Potensi Rugi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (!empty($kerugian_detail)): ?>
                                <?php foreach ($kerugian_detail as $lost): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="p-3 text-gray-500 text-xs"><?= date('d/m/Y', strtotime($lost['tgl'])) ?></td>
                                    <td class="p-3 font-semibold text-gray-800"><?= $lost['nama_produk'] ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold <?= $lost['tipe'] == 'RUSAK' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700' ?>">
                                            <?= $lost['tipe'] ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-right font-bold"><?= number_format($lost['jumlah'], 0, ',', '.') ?> btl</td>
                                    <td class="p-3 text-right text-red-600 font-bold"><?= rupiah($lost['nominal']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-gray-400 italic">Tidak ada rincian kerugian pada periode ini</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/layout_footer.php'; ?>
