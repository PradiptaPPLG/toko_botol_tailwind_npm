<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_admin = is_admin();
$root_path = '/kasir_toko/';
?>

<!-- Mobile Menu Button -->
<div class="lg:hidden fixed top-4 left-4 z-50">
    <button id="menuBtn" class="bg-blue-900 text-white p-3 rounded-lg shadow-lg hover:bg-blue-800 transition-all duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
</div>

<!-- Overlay Mobile -->
<div id="overlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity duration-300"></div>

<!-- FLEX CONTAINER UTAMA -->
<div class="flex h-screen bg-gray-100">
    
    <!-- SIDEBAR -->
    <div id="sidebar" 
         class="fixed lg:relative top-0 left-0 h-full w-64 bg-gradient-to-b from-blue-900 to-blue-800 text-white flex flex-col shadow-2xl z-50 
                transition-all duration-300 ease-in-out
                -translate-x-full lg:translate-x-0">
        
        <!-- Header -->
        <div class="p-6 border-b border-blue-700">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold flex items-center">
                    <span class="mr-2">🥤</span> 
                    <span>Kasir Botol</span>
                </h2>
                <button id="closeBtn" class="lg:hidden text-white hover:text-blue-200 text-2xl">
                    ✕
                </button>
            </div>
            <p class="text-sm text-blue-200 mt-1">
                <?= $_SESSION['user']['nama'] ?? '' ?>
                (<?= $is_admin ? 'Admin' : 'Kasir' ?>)
            </p>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 p-4 overflow-y-auto">
            <ul class="space-y-2">
                <?php if ($is_admin): ?>
                <li>
                    <a href="<?= $root_path ?>dashboard.php" 
                       class="flex items-center p-3 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:shadow-lg
                              <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-700 shadow-lg' : '' ?>">
                        <span class="text-xl mr-3">📊</span> 
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $root_path ?>modules/gudang/index.php" 
                       class="flex items-center p-3 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:shadow-lg
                              <?= strpos($_SERVER['REQUEST_URI'], '/gudang/') !== false ? 'bg-blue-700 shadow-lg' : '' ?>">
                        <span class="text-xl mr-3">🏚️</span> 
                        <span>Gudang</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $root_path ?>modules/kasir/index.php" 
                       class="flex items-center p-3 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:shadow-lg
                              <?= strpos($_SERVER['REQUEST_URI'], '/kasir/') !== false ? 'bg-blue-700 shadow-lg' : '' ?>">
                        <span class="text-xl mr-3">🛒</span> 
                        <span>Transaksi</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $root_path ?>modules/admin/laporan.php" 
                       class="flex items-center p-3 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:shadow-lg
                              <?= strpos($_SERVER['REQUEST_URI'], '/laporan') !== false ? 'bg-blue-700 shadow-lg' : '' ?>">
                        <span class="text-xl mr-3">📈</span> 
                        <span>Laporan</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $root_path ?>modules/admin/tambah_stok.php" 
                       class="flex items-center p-3 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:shadow-lg
                              <?= strpos($_SERVER['REQUEST_URI'], '/tambah_stok') !== false ? 'bg-blue-700 shadow-lg' : '' ?>">
                        <span class="text-xl mr-3">➕</span> 
                        <span>Tambah Produk</span>
                    </a>
                </li>
                <?php else: ?>
                <li>
                    <a href="<?= $root_path ?>modules/kasir/index.php" 
                       class="flex items-center p-3 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:shadow-lg bg-blue-700 shadow-lg">
                        <span class="text-xl mr-3">🛒</span> 
                        <span>Transaksi</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="pt-6 mt-6 border-t border-blue-700">
                    <a href="<?= $root_path ?>logout.php" 
                       class="flex items-center p-3 rounded-lg transition-all duration-200 hover:bg-red-600 hover:shadow-lg text-red-200 hover:text-white">
                        <span class="text-xl mr-3">🚪</span> 
                        <span>Keluar</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Footer -->
        <div class="p-4 text-xs text-blue-200 border-t border-blue-700">
            <p>© 2024 Toko Botol</p>
            <p class="mt-1">Versi 1.0.0</p>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="flex-1 overflow-auto transition-all duration-300">