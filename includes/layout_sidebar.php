<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_admin = is_admin();
require_once 'config.php';
$root_path = $root ?? '';
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
                <p class="text-2x1 font-bold flex items-center">
                    <span class="mr-2">🛒</span>
                    <span>Toko PDK</span>
                </p>
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
                    <!-- Gudang Dropdown -->
                    <li>
                        <button onclick="toggleDropdown('gudang')"
                                class="w-full flex items-center justify-between p-3 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:shadow-lg
                   <?= str_contains($_SERVER['REQUEST_URI'], '/gudang/') ? 'bg-blue-700 shadow-lg' : '' ?>">
                            <div class="flex items-center">
                                <span class="text-xl mr-3">🏚️</span>
                                <span>Gudang</span>
                            </div>
                            <span id="gudangIcon" class="transition-transform">▼</span>
                        </button>
                        <ul id="gudangDropdown" class="ml-6 mt-2 space-y-1 hidden">
                            <li>
                                <a href="<?= $root_path ?>modules/gudang/stok_masuk.php"
                                   class="block p-2 pl-4 rounded-lg text-sm hover:bg-blue-700 transition-all
                      <?= basename($_SERVER['PHP_SELF']) == 'stok_masuk.php' ? 'bg-blue-700' : '' ?>">
                                    📥 Stok Masuk
                                </a>
                            </li>
                            <li>
                                <a href="<?= $root_path ?>modules/gudang/stok_keluar.php"
                                   class="block p-2 pl-4 rounded-lg text-sm hover:bg-blue-700 transition-all
                      <?= basename($_SERVER['PHP_SELF']) == 'stok_keluar.php' ? 'bg-blue-700' : '' ?>">
                                    📤 Stok Keluar
                                </a>
                            </li>
                            <li>
                                <a href="<?= $root_path ?>modules/gudang/stok_opname.php"
                                   class="block p-2 pl-4 rounded-lg text-sm hover:bg-blue-700 transition-all
                      <?= basename($_SERVER['PHP_SELF']) == 'stok_opname.php' ? 'bg-blue-700' : '' ?>">
                                    📋 Stok Opname
                                </a>
                            </li>
                            <li>
                                <a href="<?= $root_path ?>modules/gudang/pengeluaran.php"
                                   class="block p-2 pl-4 rounded-lg text-sm hover:bg-blue-700 transition-all
                      <?= basename($_SERVER['PHP_SELF']) == 'pengeluaran.php' ? 'bg-blue-700' : '' ?>">
                                    💸 Pengeluaran
                                </a>
                            </li>
                        </ul>
                    </li>
                <!-- Transaksi Dropdown -->
                <li>
                    <button onclick="toggleDropdown('transaksi')"
                            class="w-full flex items-center justify-between p-3 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:shadow-lg
                                   <?= str_contains($_SERVER['REQUEST_URI'], '/kasir/') ? 'bg-blue-700 shadow-lg' : '' ?>">
                        <div class="flex items-center">
                            <span class="text-xl mr-3">🛒</span>
                            <span>Transaksi</span>
                        </div>
                        <span id="transaksiIcon" class="transition-transform">▼</span>
                    </button>
                    <ul id="transaksiDropdown" class="ml-6 mt-2 space-y-1 hidden">
                        <li>
                            <a href="<?= $root_path ?>modules/kasir/pembeli.php"
                               class="block p-2 pl-4 rounded-lg text-sm hover:bg-blue-700 transition-all
                                      <?= (basename($_SERVER['PHP_SELF']) == 'pembeli.php') ? 'bg-blue-700' : '' ?>">
                                🛒 Transaksi Pembeli
                            </a>
                        </li>
                        <li>
                            <a href="<?= $root_path ?>modules/kasir/penjual.php"
                               class="block p-2 pl-4 rounded-lg text-sm hover:bg-blue-700 transition-all
                                      <?= (basename($_SERVER['PHP_SELF']) == 'penjual.php') ? 'bg-blue-700' : '' ?>">
                                🤝 Transaksi Penjual
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Info Cabang Dropdown -->
                <li>
                    <button onclick="toggleDropdown('infoCabang')"
                            class="w-full flex items-center justify-between p-3 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:shadow-lg
                                   <?= str_contains($_SERVER['REQUEST_URI'], '/info_cabang') ? 'bg-blue-700 shadow-lg' : '' ?>">
                        <div class="flex items-center">
                            <span class="text-xl mr-3">🏢</span>
                            <span>Info Cabang</span>
                        </div>
                        <span id="infoCabangIcon" class="transition-transform">▼</span>
                    </button>
                    <ul id="infoCabangDropdown" class="ml-6 mt-2 space-y-1 hidden">
                        <?php foreach (get_cabang() as $c): ?>
                        <li>
                            <a href="<?= $root_path ?>modules/admin/info_cabang.php?cabang=<?= $c['id'] ?>"
                               class="block p-2 pl-4 rounded-lg text-sm hover:bg-blue-700 transition-all">
                                📍 <?= $c['nama_cabang'] ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <!-- Laporan Dropdown -->
                <li>
                    <button onclick="toggleDropdown('laporan')"
                            class="w-full flex items-center justify-between p-3 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:shadow-lg
                                   <?= str_contains($_SERVER['REQUEST_URI'], '/laporan') ? 'bg-blue-700 shadow-lg' : '' ?>">
                        <div class="flex items-center">
                            <span class="text-xl mr-3">📈</span>
                            <span>Laporan</span>
                        </div>
                        <span id="laporanIcon" class="transition-transform">▼</span>
                    </button>
                    <ul id="laporanDropdown" class="ml-6 mt-2 space-y-1 hidden">
                        <li>
                            <a href="<?= $root_path ?>modules/admin/laporan_penjualan.php"
                               class="block p-2 pl-4 rounded-lg text-sm hover:bg-blue-700 transition-all
                                      <?= basename($_SERVER['PHP_SELF']) == 'laporan_penjualan.php' ? 'bg-blue-700' : '' ?>">
                                💰 Laporan Penjualan
                            </a>
                        </li>
                        <li>
                            <a href="<?= $root_path ?>modules/admin/laporan_pembelian.php"
                               class="block p-2 pl-4 rounded-lg text-sm hover:bg-blue-700 transition-all
                                      <?= basename($_SERVER['PHP_SELF']) == 'laporan_pembelian.php' ? 'bg-blue-700' : '' ?>">
                                📦 Laporan Stok
                            </a>
                        </li>
                        <li>
                            <a href="<?= $root_path ?>modules/admin/rekap.php"
                               class="block p-2 pl-4 rounded-lg text-sm hover:bg-blue-700 transition-all
                                      <?= basename($_SERVER['PHP_SELF']) == 'rekap.php' ? 'bg-blue-700' : '' ?>">
                                📊 Rekap Keuntungan
                            </a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a href="<?= $root_path ?>modules/admin/tambah_stok.php"
                       class="flex items-center p-3 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:shadow-lg
                               <?= str_contains($_SERVER['REQUEST_URI'], '/tambah_stok') ? 'bg-blue-700 shadow-lg' : '' ?>">
                        <span class="text-xl mr-3">➕</span>
                        <span>Tambah Produk</span>
                    </a>
                </li>
                <?php endif; ?>

                <li class="pt-6 mt-6 border-t border-blue-700">
                    <a href="<?= $root_path ?>logout.php"
                       onclick="event.preventDefault();
                                if(typeof confirmLogout === 'function') {
                                    confirmLogout().then(res => { if(res) window.location.href='<?= $root_path ?>logout.php'; });
                                } else if(confirm('Yakin ingin keluar?')) {
                                    window.location.href='<?= $root_path ?>logout.php';
                                }"
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

<script>
// Dropdown Toggle Function
function toggleDropdown(id) {
    const dropdown = document.getElementById(id + 'Dropdown');
    const icon = document.getElementById(id + 'Icon');

    if (dropdown.classList.contains('hidden')) {
        dropdown.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
    } else {
        dropdown.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
    }
}

// Mobile Menu
document.getElementById('menuBtn')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.add('translate-x-0');
    document.getElementById('sidebar').classList.remove('-translate-x-full');
    document.getElementById('overlay').classList.remove('hidden');
});

document.getElementById('closeBtn')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.remove('translate-x-0');
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('overlay').classList.add('hidden');
});

document.getElementById('overlay')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.remove('translate-x-0');
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('overlay').classList.add('hidden');
});

// Buka dropdown jika halaman aktif
['gudang', 'transaksi', 'infoCabang', 'laporan'].forEach(id => {
    const dropdown = document.getElementById(id + 'Dropdown');
    const icon = document.getElementById(id + 'Icon');
    const isKasirMatch = (id === 'transaksi' && window.location.pathname.includes('/kasir/'));
    const isOtherMatch = window.location.pathname.includes('/' + id.toLowerCase() + '/');
    
    if (dropdown && (isKasirMatch || isOtherMatch)) {
        dropdown.classList.remove('hidden');
        if (icon) icon.style.transform = 'rotate(180deg)';
    }
});
</script>