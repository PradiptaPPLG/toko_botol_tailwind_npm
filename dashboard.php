<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!is_login()) redirect('login.php');
if (!is_admin()) redirect('modules/kasir/index.php');

$title = 'Dashboard Admin';
include 'includes/layout_header.php';
include 'includes/layout_sidebar.php';

// Data untuk dashboard
$total_produk = query("SELECT COUNT(*) as total FROM produk")[0]['total'] ?? 0;
$total_transaksi_hari_ini = query("SELECT COUNT(*) as total, SUM(total_harga) as nominal FROM transaksi WHERE DATE(created_at) = CURDATE()")[0] ?? ['total' => 0, 'nominal' => 0];
$total_pengeluaran_hari_ini = query("SELECT SUM(nominal) as total FROM pengeluaran WHERE DATE(created_at) = CURDATE()")[0]['total'] ?? 0;
$total_stok_gudang = query("SELECT SUM(stok_gudang) as total FROM produk")[0]['total'] ?? 0;
$cek_hilang = cek_selisih_stok();

// Transaksi terbaru
$transaksi_terbaru = query("
    SELECT t.*, p.nama_produk, c.nama_cabang 
    FROM transaksi t 
    JOIN produk p ON t.produk_id = p.id 
    JOIN cabang c ON t.cabang_id = c.id 
    ORDER BY t.created_at DESC 
    LIMIT 10
");
?>

<!-- CONTENT DASHBOARD - TANPA DIV FLEX! LANGSUNG ISI -->
<div class="p-4 sm:p-6 lg:p-8">
    <h1 class="judul text-2xl sm:text-3xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="mr-2">📊</span> Dashboard Admin
    </h1>
    
    <!-- Statistik Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
        <!-- Total Produk -->
        <div class="bg-white rounded-lg shadow p-4 sm:p-6 border-l-4 sm:border-l-8 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs sm:text-sm uppercase">Total Produk</p>
                    <p class="text-2xl sm:text-4xl font-bold text-gray-800"><?= $total_produk ?></p>
                </div>
                <span class="text-3xl sm:text-5xl text-blue-500">📦</span>
            </div>
        </div>
        
        <!-- Transaksi Hari Ini -->
        <div class="bg-white rounded-lg shadow p-4 sm:p-6 border-l-4 sm:border-l-8 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs sm:text-sm uppercase">Transaksi Hari Ini</p>
                    <p class="text-2xl sm:text-4xl font-bold text-gray-800"><?= $total_transaksi_hari_ini['total'] ?? 0 ?></p>
                    <p class="text-xs sm:text-sm text-green-600"><?= rupiah($total_transaksi_hari_ini['nominal'] ?? 0) ?></p>
                </div>
                <span class="text-3xl sm:text-5xl text-green-500">💰</span>
            </div>
        </div>
        
        <!-- Pengeluaran Hari Ini -->
        <div class="bg-white rounded-lg shadow p-4 sm:p-6 border-l-4 sm:border-l-8 border-yellow-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs sm:text-sm uppercase">Pengeluaran Hari Ini</p>
                    <p class="text-2xl sm:text-4xl font-bold text-gray-800"><?= rupiah($total_pengeluaran_hari_ini) ?></p>
                </div>
                <span class="text-3xl sm:text-5xl text-yellow-500">💸</span>
            </div>
        </div>
        
        <!-- Stok Gudang -->
        <div class="bg-white rounded-lg shadow p-4 sm:p-6 border-l-4 sm:border-l-8 border-purple-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs sm:text-sm uppercase">Stok Gudang</p>
                    <p class="text-2xl sm:text-4xl font-bold text-gray-800"><?= $total_stok_gudang ?></p>
                    <p class="text-xs sm:text-sm text-purple-600">Botol</p>
                </div>
                <span class="text-3xl sm:text-5xl text-purple-500">🏚️</span>
            </div>
        </div>
    </div>
    
    <!-- Grafik & Transaksi -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <!-- Kurva Kerugian -->
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <h2 class="text-lg sm:text-xl font-bold mb-4 flex items-center">
                <span class="mr-2">📉</span> Kurva Kerugian (7 Hari)
            </h2>
            <?php
            $kerugian = query("
                SELECT DATE(created_at) as tanggal, 
                       SUM(selisih * (SELECT harga_jual FROM produk WHERE id = produk_id)) as nominal
                FROM stock_opname 
                WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY tanggal DESC
            ");
            ?>
            <div class="space-y-3">
                <?php if (!empty($kerugian)): ?>
                    <?php foreach ($kerugian as $k): ?>
                    <div class="flex items-center text-sm sm:text-base">
                        <span class="w-20 sm:w-32 text-gray-600"><?= date('d/m', strtotime($k['tanggal'])) ?></span>
                        <div class="flex-1 h-3 sm:h-4 bg-gray-200 rounded-full mx-2 sm:mx-3">
                            <div class="h-3 sm:h-4 bg-red-500 rounded-full" style="width: <?= min(100, ($k['nominal'] / 500000) * 100) ?>%"></div>
                        </div>
                        <span class="text-red-600 font-bold text-xs sm:text-sm"><?= rupiah($k['nominal'] ?? 0) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4 text-sm sm:text-base">Belum ada data kerugian</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Transaksi Terbaru -->
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <h2 class="text-lg sm:text-xl font-bold mb-4 flex items-center">
                <span class="mr-2">🔄</span> Transaksi Terbaru
            </h2>
            <div class="overflow-x-auto -mx-4 sm:-mx-6 lg:mx-0">
                <div class="inline-block min-w-full align-middle">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase">Waktu</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase">Produk</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase">Kasir</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($transaksi_terbaru)): ?>
                                <?php foreach ($transaksi_terbaru as $t): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-2 sm:py-3 text-xs sm:text-sm text-gray-900"><?= date('H:i', strtotime($t['created_at'])) ?></td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-3 text-xs sm:text-sm text-gray-900"><?= $t['nama_produk'] ?></td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-3 text-xs sm:text-sm text-gray-900"><?= $t['nama_kasir'] ?></td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-3 text-xs sm:text-sm font-bold text-gray-900"><?= rupiah($t['total_harga']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-4 sm:px-6 py-8 text-center text-sm sm:text-base text-gray-500">
                                        Belum ada transaksi
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/layout_footer.php'; ?>