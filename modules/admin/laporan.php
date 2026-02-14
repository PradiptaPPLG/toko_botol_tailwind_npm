<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
if (!is_login() || !is_admin()) redirect('../../login.php');

$title = 'Laporan & Rekap';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$cabang_id = $_GET['cabang'] ?? 0;

$cabang_list = get_cabang();

// Rekap per cabang
$rekap_cabang = [];
foreach ($cabang_list as $c) {
    $rekap = query("
        SELECT 
            COUNT(*) as total_transaksi,
            SUM(CASE WHEN tipe = 'pembeli' THEN total_harga ELSE 0 END) as total_penjualan,
            SUM(CASE WHEN tipe = 'penjual' THEN total_harga ELSE 0 END) as total_pembelian
        FROM transaksi 
        WHERE cabang_id = {$c['id']} AND DATE(created_at) = '$tanggal'
    ")[0];
    
    $rekap_cabang[$c['id']] = [
        'nama' => $c['nama_cabang'],
        'transaksi' => $rekap['total_transaksi'] ?? 0,
        'penjualan' => $rekap['total_penjualan'] ?? 0,
        'pembelian' => $rekap['total_pembelian'] ?? 0
    ];
}

// Total semua cabang
$total_penjualan = array_sum(array_column($rekap_cabang, 'penjualan'));
$total_pembelian = array_sum(array_column($rekap_cabang, 'pembelian'));

// Transaksi detail
$transaksi = query("
    SELECT t.*, p.nama_produk, c.nama_cabang 
    FROM transaksi t 
    JOIN produk p ON t.produk_id = p.id 
    JOIN cabang c ON t.cabang_id = c.id 
    WHERE DATE(t.created_at) = '$tanggal'
    " . ($cabang_id > 0 ? " AND t.cabang_id = $cabang_id" : "") . "
    ORDER BY t.created_at DESC
");

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 30;
$offset = ($page - 1) * $limit;
$total_data = count($transaksi);
$total_pages = ceil($total_data / $limit);
$transaksi_page = array_slice($transaksi, $offset, $limit);
?>
<div class="p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="mr-3">📈</span> LAPORAN & REKAP HARIAN
    </h1>
    
    <!-- Filter -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-gray-700 font-medium mb-2">📅 Tanggal</label>
                <input type="date" name="tanggal" value="<?= $tanggal ?>" class="w-full border rounded-lg p-3 text-lg">
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">🏢 Cabang</label>
                <select name="cabang" class="w-full border rounded-lg p-3 text-lg">
                    <option value="0">Semua Cabang</option>
                    <?php foreach ($cabang_list as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $cabang_id ? 'selected' : '' ?>>
                        <?= $c['nama_cabang'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg text-lg">
                    🔍 TAMPILKAN
                </button>
            </div>
        </form>
    </div>
    
    <!-- Rekap Gabungan 1 Hari -->
    <div class="bg-gradient-to-r from-blue-800 to-indigo-900 text-white p-8 rounded-lg shadow-lg mb-6">
        <h2 class="text-2xl font-bold mb-6 flex items-center">
            <span class="mr-3">📊</span> REKAP GABUNGAN <?= date('d/m/Y', strtotime($tanggal)) ?>
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white bg-opacity-20 p-6 rounded-lg">
                <p class="text-sm opacity-90">💰 TOTAL PENJUALAN</p>
                <p class="text-4xl font-bold mt-2"><?= rupiah($total_penjualan) ?></p>
            </div>
            <div class="bg-white bg-opacity-20 p-6 rounded-lg">
                <p class="text-sm opacity-90">💸 TOTAL PEMBELIAN</p>
                <p class="text-4xl font-bold mt-2"><?= rupiah($total_pembelian) ?></p>
            </div>
            <div class="bg-white bg-opacity-20 p-6 rounded-lg">
                <p class="text-sm opacity-90">📈 KEUNTUNGAN KOTOR</p>
                <p class="text-4xl font-bold mt-2 <?= ($total_penjualan - $total_pembelian) < 0 ? 'text-red-300' : '' ?>">
                    <?= rupiah($total_penjualan - $total_pembelian) ?>
                </p>
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
            <div class="flex justify-between mt-1">
                <span>Pembelian:</span>
                <span class="font-bold text-yellow-600"><?= rupiah($r['pembelian']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Detail Transaksi -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gray-800 text-white p-4">
            <h2 class="text-xl font-bold">📋 DETAIL TRANSAKSI</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Waktu</th>
                        <th class="p-3 text-left">Invoice</th>
                        <th class="p-3 text-left">Cabang</th>
                        <th class="p-3 text-left">Kasir</th>
                        <th class="p-3 text-left">Produk</th>
                        <th class="p-3 text-left">Tipe</th>
                        <th class="p-3 text-left">Jumlah</th>
                        <th class="p-3 text-left">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transaksi_page as $t): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3"><?= date('H:i:s', strtotime($t['created_at'])) ?></td>
                        <td class="p-3 font-mono text-sm"><?= $t['no_invoice'] ?></td>
                        <td class="p-3"><?= $t['nama_cabang'] ?></td>
                        <td class="p-3"><?= $t['nama_kasir'] ?></td>
                        <td class="p-3"><?= $t['nama_produk'] ?></td>
                        <td class="p-3">
                            <?php if ($t['tipe'] == 'pembeli'): ?>
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">PEMBELI</span>
                            <?php else: ?>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">PENJUAL</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3"><?= $t['jumlah'] ?> <?= $t['satuan'] ?></td>
                        <td class="p-3 font-bold"><?= rupiah($t['total_harga']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="bg-gray-50 p-4 flex justify-center">
            <div class="flex space-x-2">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?tanggal=<?= $tanggal ?>&cabang=<?= $cabang_id ?>&page=<?= $i ?>" 
                   class="px-4 py-2 <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?> rounded-lg">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include '../../includes/layout_footer.php'; ?>