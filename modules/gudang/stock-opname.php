<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php'; // <=== TAMBAHKAN!
if (!is_login() || !is_admin()) redirect('../../login.php');

$title = 'Laporan Stock Opname';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$total_data = query("SELECT COUNT(*) as total FROM stock_opname")[0]['total'];
$total_pages = ceil($total_data / $limit);

$data = query("
    SELECT so.*, p.nama_produk 
    FROM stock_opname so 
    JOIN produk p ON so.produk_id = p.id 
    ORDER BY so.created_at DESC 
    LIMIT $offset, $limit
");

$total_hilang = query("SELECT SUM(selisih) as total FROM stock_opname WHERE selisih > 0")[0]['total'] ?? 0;
$total_rupiah_hilang = query("
    SELECT SUM(so.selisih * p.harga_jual) as total 
    FROM stock_opname so 
    JOIN produk p ON so.produk_id = p.id 
    WHERE so.selisih > 0
")[0]['total'] ?? 0;
?>
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center">
            <span class="mr-3">📋</span> LAPORAN STOCK OPNAME
        </h1>
        <a href="index.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
            ← Kembali ke Gudang
        </a>
    </div>
    
    <!-- Total Kerugian -->
    <div class="bg-linear-to-r from-red-600 to-red-800 text-white p-6 rounded-lg shadow-lg mb-6">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-2xl font-bold mb-2">💰 TOTAL KERUGIAN</p>
                <p class="text-sm opacity-90">Berdasarkan stock opname</p>
            </div>
            <div class="text-right">
                <p class="text-5xl font-bold"><?= rupiah($total_rupiah_hilang) ?></p>
                <p class="text-2xl mt-2"><?= $total_hilang ?> Botol Hilang</p>
            </div>
        </div>
    </div>
    
    <!-- Tabel Stock Opname -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="p-3 text-left">Tanggal</th>
                        <th class="p-3 text-left">Produk</th>
                        <th class="p-3 text-left">Stok Sistem</th>
                        <th class="p-3 text-left">Stok Fisik</th>
                        <th class="p-3 text-left">Selisih</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Petugas</th>
                        <th class="p-3 text-left">Estimasi Rugi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $d): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3"><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></td>
                        <td class="p-3 font-bold"><?= $d['nama_produk'] ?></td>
                        <td class="p-3"><?= $d['stok_sistem'] ?></td>
                        <td class="p-3"><?= $d['stok_fisik'] ?></td>
                        <td class="p-3 font-bold <?= $d['selisih'] > 0 ? 'text-red-600' : ($d['selisih'] < 0 ? 'text-green-600' : '') ?>">
                            <?= $d['selisih'] ?>
                        </td>
                        <td class="p-3">
                            <?php if ($d['status'] == 'HILANG'): ?>
                            <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-bold">HILANG</span>
                            <?php elseif ($d['status'] == 'LEBIH'): ?>
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs">LEBIH</span>
                            <?php else: ?>
                            <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs">SESUAI</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3"><?= $d['petugas'] ?></td>
                        <td class="p-3 font-bold text-red-600">
                            <?php 
                            $harga_jual = query("SELECT harga_jual FROM produk WHERE id = {$d['produk_id']}")[0]['harga_jual'];
                            echo $d['selisih'] > 0 ? rupiah($d['selisih'] * $harga_jual) : '-';
                            ?>
                        </td>
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
                <a href="?page=<?= $i ?>" class="px-4 py-2 <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?> rounded-lg">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include '../../includes/layout_footer.php'; ?>