<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!is_login()) redirect('login.php');
if (!is_admin()) redirect('modules/kasir/index.php');

$title = 'Dashboard Admin';
include 'includes/layout_header.php';
include 'includes/layout_sidebar.php';
include 'includes/modal_confirm.php';

// Data untuk dashboard
$produk_counts = query("SELECT status, COUNT(*) as counts FROM produk GROUP BY status");
$total_produk_aktif = 0;
$total_produk_nonaktif = 0;
foreach ($produk_counts as $pc) {
    if ($pc['status'] == 'active') $total_produk_aktif = $pc['counts'];
    else $total_produk_nonaktif = $pc['counts'];
}
$total_transaksi_hari_ini = query("SELECT COUNT(*) as total, SUM(total_harga) as nominal FROM transaksi_header WHERE DATE(created_at) = CURDATE()")[0] ?? ['total' => 0, 'nominal' => 0];
$total_pengeluaran_hari_ini = query("SELECT SUM(nominal) as total FROM pengeluaran WHERE DATE(created_at) = CURDATE() AND deleted_at IS NULL")[0]['total'] ?? 0;
$total_stok_gudang = query("SELECT SUM(stok_gudang) as total FROM produk")[0]['total'] ?? 0;
$cek_hilang = cek_selisih_stok();

// Transaksi terbaru - using new structure
$transaksi_terbaru = query("
    SELECT th.*, c.nama_cabang
    FROM transaksi_header th
    JOIN cabang c ON th.cabang_id = c.id
    ORDER BY th.created_at DESC
    LIMIT 10
");
?>

<!-- CONTENT DASHBOARD - TANPA DIV FLEX! LANGSUNG ISI -->
<div class="p-4 sm:p-6 lg:p-8">
    <h1 class="judul text-2xl sm:text-3xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="mr-2">📊</span> Dashboard Admin
    </h1>
    
    <!-- Statistik Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-6 mb-6 sm:mb-8">
        <!-- Total Produk Aktif -->
        <div class="bg-white rounded-lg shadow p-4 sm:p-6 border-l-4 sm:border-l-8 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs sm:text-sm uppercase font-bold">Produk Aktif</p>
                    <p class="text-2xl sm:text-4xl font-bold text-gray-800"><?= $total_produk_aktif ?></p>
                </div>
                <span class="text-3xl sm:text-5xl text-blue-500">📦</span>
            </div>
        </div>

        <!-- Total Produk Nonaktif -->
        <div class="bg-white rounded-lg shadow p-4 sm:p-6 border-l-4 sm:border-l-8 border-gray-400">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs sm:text-sm uppercase font-bold">Produk Nonaktif</p>
                    <p class="text-2xl sm:text-4xl font-bold text-gray-800"><?= $total_produk_nonaktif ?></p>
                </div>
                <span class="text-3xl sm:text-5xl text-gray-400 opacity-50">📤</span>
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
                    <p class="text-2xl sm:text-4xl font-bold text-gray-800"><?= number_format($total_stok_gudang, 0, ',', '.') ?></p>
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
                <span class="mr-2">📉</span> Kurva Kerugian Terbaru
            </h2>
            <?php
            $kerugian = query("
                SELECT tanggal, SUM(nominal) as nominal FROM (
                    SELECT DATE(tanggal) as tanggal, 
                           SUM(selisih * COALESCE((SELECT AVG(harga_satuan) FROM transaksi_detail WHERE produk_id = stock_opname.produk_id), 0)) as nominal
                    FROM stock_opname 
                    WHERE status = 'HILANG' AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY DATE(tanggal)
                    UNION ALL
                    SELECT DATE(created_at) as tanggal,
                           SUM(jumlah * COALESCE((SELECT AVG(harga_satuan) FROM transaksi_detail WHERE produk_id = stok_keluar.produk_id), 0)) as nominal
                    FROM stok_keluar
                    WHERE kondisi = 'rusak' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY DATE(created_at)
                ) as combined
                GROUP BY tanggal
                ORDER BY tanggal DESC
            ");
            ?>
            <div class="space-y-3 mb-6">
                <?php if (!empty($kerugian)): ?>
                    <?php foreach ($kerugian as $k): ?>
                    <div class="flex items-center text-sm sm:text-base">
                        <span class="w-20 sm:w-32 text-gray-600"><?= date('d/m', strtotime($k['tanggal'])) ?></span>
                        <div class="flex-1 h-2 sm:h-3 bg-gray-200 rounded-full mx-2 sm:mx-3">
                            <div class="progress-bar" style="width: <?= min(100, ($k['nominal'] / 500000) * 100) ?>%"></div>
                        </div>
                        <span class="text-red-600 font-bold text-xs sm:text-sm"><?= rupiah($k['nominal'] ?? 0) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4 text-sm sm:text-base">Belum ada data kerugian</p>
                <?php endif; ?>
            </div>

            <!-- Detail List Kerugian -->
            <div class="border-t pt-4">
                <h3 class="text-sm font-bold text-gray-700 uppercase mb-3 tracking-wider">📦 Detail Kerugian Terbaru</h3>
                <div class="space-y-2 max-h-60 overflow-y-auto">
                    <?php
                    $detail_kerugian = query("
                        SELECT * FROM (
                            SELECT 
                                'HILANG (SO)' as tipe,
                                p.nama_produk,
                                so.selisih as jumlah,
                                (so.selisih * COALESCE((SELECT AVG(harga_satuan) FROM transaksi_detail WHERE produk_id = so.produk_id), 0)) as nominal,
                                so.tanggal as tgl
                            FROM stock_opname so
                            JOIN produk p ON so.produk_id = p.id
                            WHERE so.status = 'HILANG' AND so.is_cancelled = 0
                            
                            UNION ALL
                            
                            SELECT 
                                'RUSAK' as tipe,
                                p.nama_produk,
                                sk.jumlah,
                                (sk.jumlah * COALESCE((SELECT AVG(harga_satuan) FROM transaksi_detail WHERE produk_id = sk.produk_id), 0)) as nominal,
                                sk.created_at as tgl
                            FROM stok_keluar sk
                            JOIN produk p ON sk.produk_id = p.id
                            WHERE sk.kondisi = 'rusak'
                        ) as losses
                        ORDER BY tgl DESC
                        LIMIT 10
                    ");

                    if (!empty($detail_kerugian)):
                        foreach ($detail_kerugian as $dk):
                    ?>
                        <div class="flex justify-between items-center p-2 hover:bg-gray-50 rounded border-b border-gray-100 last:border-0">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold <?= $dk['tipe'] == 'RUSAK' ? 'text-red-600' : 'text-orange-600' ?>"><?= $dk['tipe'] ?></span>
                                <span class="text-sm font-semibold text-gray-800"><?= $dk['nama_produk'] ?></span>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-bold text-gray-900"><?= $dk['jumlah'] ?> btl</p>
                                <p class="text-[10px] text-red-500 font-bold">-<?= rupiah($dk['nominal']) ?></p>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <p class="text-xs text-gray-400 text-center py-4 italic">Tidak ada detail kerugian</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Keuntungan Bulan Ini -->
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <h2 class="text-lg sm:text-xl font-bold mb-4 flex items-center">
                <span class="mr-2">📈</span> Keuntungan Bulan Ini
            </h2>
            <?php
            // Hitung Penjualan Bulan Ini
            $penjualan_bulan_ini = query("
                SELECT SUM(total_harga) as total 
                FROM transaksi_header 
                WHERE MONTH(created_at) = MONTH(CURDATE()) 
                  AND YEAR(created_at) = YEAR(CURDATE())
            ")[0]['total'] ?? 0;

            // Hitung Pembelian (Stok Masuk) Bulan Ini
            $pembelian_bulan_ini = query("
                SELECT SUM(total_belanja) as total 
                FROM stok_masuk 
                WHERE MONTH(created_at) = MONTH(CURDATE()) 
                  AND YEAR(created_at) = YEAR(CURDATE())
            ")[0]['total'] ?? 0;

            // Hitung Kerugian (Rusak & Hilang) Bulan Ini
            $kerugian_bulan_ini = query("
                SELECT SUM(nominal) as total FROM (
                    SELECT SUM(selisih * COALESCE((SELECT AVG(harga_satuan) FROM transaksi_detail WHERE produk_id = stock_opname.produk_id), 0)) as nominal
                    FROM stock_opname 
                    WHERE status = 'HILANG' 
                      AND MONTH(tanggal) = MONTH(CURDATE()) 
                      AND YEAR(tanggal) = YEAR(CURDATE())
                    UNION ALL
                    SELECT SUM(jumlah * COALESCE((SELECT AVG(harga_satuan) FROM transaksi_detail WHERE produk_id = stok_keluar.produk_id), 0)) as nominal
                    FROM stok_keluar
                    WHERE kondisi = 'rusak' 
                      AND MONTH(created_at) = MONTH(CURDATE()) 
                      AND YEAR(created_at) = YEAR(CURDATE())
                ) as combined_loss
            ")[0]['total'] ?? 0;

            $keuntungan = $penjualan_bulan_ini - $pembelian_bulan_ini - $kerugian_bulan_ini;
            $is_rugi = $keuntungan < 0;
            ?>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                    <span class="text-gray-600 text-sm">Total Penjualan</span>
                    <span class="font-bold text-blue-700"><?= rupiah($penjualan_bulan_ini) ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-indigo-50 rounded-lg">
                    <span class="text-gray-600 text-sm">Total Belanja Stok</span>
                    <span class="font-bold text-indigo-700"><?= rupiah($pembelian_bulan_ini) ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                    <span class="text-gray-600 text-sm">Total Kerugian (Rusak/Hilang)</span>
                    <span class="font-bold text-red-700"><?= rupiah($kerugian_bulan_ini) ?></span>
                </div>
                <div class="pt-4 border-t flex flex-col items-center">
                    <span class="text-gray-500 text-xs uppercase font-bold tracking-wider mb-1">Status Keuntungan</span>
                    <p class="text-3xl font-black <?= $is_rugi ? 'text-red-600' : 'text-green-600' ?>">
                        <?= rupiah($keuntungan) ?>
                    </p>
                    <?php if ($is_rugi): ?>
                        <div class="mt-2 px-4 py-1 bg-red-600 text-white text-xs font-bold rounded-full animate-pulse">
                            ⚠️ BELUM BALIK MODAL
                        </div>
                    <?php else: ?>
                        <div class="mt-2 px-4 py-1 bg-green-600 text-white text-xs font-bold rounded-full">
                            ✅ SUDAH UNTUNG
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/layout_footer.php'; ?>
