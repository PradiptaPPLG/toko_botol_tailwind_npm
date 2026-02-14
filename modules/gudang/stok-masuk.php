<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php'; 
if (!is_login() || !is_admin()) redirect('../../login.php');

$title = 'Riwayat Stok Masuk';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$total_data = query("SELECT COUNT(*) as total FROM stok_masuk")[0]['total'];
$total_pages = ceil($total_data / $limit);

$data = query("
    SELECT sm.*, p.nama_produk 
    FROM stok_masuk sm 
    JOIN produk p ON sm.produk_id = p.id 
    ORDER BY sm.created_at DESC 
    LIMIT $offset, $limit
");
?>
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center">
            <span class="mr-3">📦</span> RIWAYAT STOK MASUK
        </h1>
        <a href="index.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
            ← Kembali ke Gudang
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="p-3 text-left">Tanggal</th>
                        <th class="p-3 text-left">Produk</th>
                        <th class="p-3 text-left">Jumlah</th>
                        <th class="p-3 text-left">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $d): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3"><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></td>
                        <td class="p-3 font-bold"><?= $d['nama_produk'] ?></td>
                        <td class="p-3 text-green-600 font-bold">+<?= $d['jumlah'] ?> botol</td>
                        <td class="p-3"><?= $d['keterangan'] ?? '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
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