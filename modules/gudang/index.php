<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!is_login()) redirect('../../login.php');

$title = 'Halaman Kasir - ' . $_SESSION['user']['nama'];
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';
// GUDANG TIDAK PAKE CABANG ID, LANGSUNG AJA
$produk = get_produk();
$cek_hilang = cek_selisih_stok();
$cabang = get_cabang(); // BUAT DROPDOWN TRANSFER
// Proses tambah stok
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tambah_stok'])) {
        $produk_id = $_POST['produk_id'];
        $jumlah = intval($_POST['jumlah']);
        $keterangan = escape_string($_POST['keterangan']);
        
        // Update stok gudang
        execute("UPDATE produk SET stok_gudang = stok_gudang + $jumlah WHERE id = $produk_id");
        
        // Catat stok masuk
        execute("INSERT INTO stok_masuk (produk_id, jumlah, keterangan) VALUES ($produk_id, $jumlah, '$keterangan')");
        
        $success = "Stok berhasil ditambahkan!";
    }
    
    if (isset($_POST['stok_keluar'])) {
        $produk_id = $_POST['produk_id'];
        $jumlah = intval($_POST['jumlah']);
        $kondisi = $_POST['kondisi'];
        $cabang_tujuan = ($kondisi == 'transfer') ? $_POST['cabang_tujuan'] : 'NULL';
        $keterangan = escape_string($_POST['keterangan']);
        
        // Cek stok
        $stok_gudang = get_stok_gudang($produk_id);
        if ($stok_gudang >= $jumlah) {
            // Kurangi stok gudang
            execute("UPDATE produk SET stok_gudang = stok_gudang - $jumlah WHERE id = $produk_id");
            
            // Jika transfer, tambah stok cabang
            if ($kondisi == 'transfer' && $cabang_tujuan != 'NULL') {
                $check = query("SELECT * FROM stok_cabang WHERE produk_id = $produk_id AND cabang_id = $cabang_tujuan");
                if (count($check) > 0) {
                    execute("UPDATE stok_cabang SET stok = stok + $jumlah WHERE produk_id = $produk_id AND cabang_id = $cabang_tujuan");
                }
            }
            
            // Catat stok keluar
            $sql = "INSERT INTO stok_keluar (produk_id, jumlah, kondisi, cabang_tujuan, keterangan) 
                    VALUES ($produk_id, $jumlah, '$kondisi', " . ($cabang_tujuan == 'NULL' ? 'NULL' : $cabang_tujuan) . ", '$keterangan')";
            execute($sql);
            
            $success = "Stok berhasil dikeluarkan!";
        } else {
            $error = "Stok gudang tidak mencukupi!";
        }
    }
}

$produk = get_produk();
$cek_hilang = cek_selisih_stok();

// Stok opname
if (isset($_POST['stock_opname'])) {
    $produk_id = $_POST['produk_id'];
    $stok_fisik = intval($_POST['stok_fisik']);
    $petugas = escape_string($_POST['petugas']);
    $tanggal = $_POST['tanggal'];
    
    $stok_sistem = get_stok_gudang($produk_id);
    $selisih = $stok_sistem - $stok_fisik;
    $status = $selisih > 0 ? 'HILANG' : ($selisih < 0 ? 'LEBIH' : 'SESUAI');
    
    execute("INSERT INTO stock_opname (produk_id, stok_sistem, stok_fisik, selisih, status, petugas, tanggal) 
             VALUES ($produk_id, $stok_sistem, $stok_fisik, $selisih, '$status', '$petugas', '$tanggal')");
    
    $success_so = "Stock Opname selesai! Selisih: $selisih botol ($status)";
}

// Pengeluaran
if (isset($_POST['tambah_pengeluaran'])) {
    $nominal = intval($_POST['nominal']);
    $keterangan = escape_string($_POST['keterangan']);
    
    execute("INSERT INTO pengeluaran (nominal, keterangan) VALUES ($nominal, '$keterangan')");
    $success = "Pengeluaran dicatat!";
}

$cabang = get_cabang();
?>
<div class="p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="mr-3">🏚️</span> MANAJEMEN GUDANG
    </h1>
    
    <!-- PERINGATAN BESAR -->
    <?php if (count($cek_hilang) > 0): ?>
    <div class="bg-red-600 text-white p-6 rounded-lg mb-6 shadow-lg border-4 border-red-800">
        <div class="flex items-center text-2xl font-bold mb-4">
            <span class="text-5xl mr-4">⚠️⚠️⚠️</span>
            <span>PERINGATAN KRITIS! STOK TIDAK SESUAI</span>
        </div>
        <?php foreach ($cek_hilang as $w): ?>
        <div class="bg-red-700 p-4 mb-3 rounded flex justify-between items-center text-xl">
            <span class="font-bold"><?= $w['produk'] ?></span>
            <span>Stok Gudang: <?= $w['stok_gudang'] ?> botol</span>
            <span>Stok Kasir: <?= $w['stok_kasir'] ?> botol</span>
            <span class="bg-red-800 px-4 py-2 rounded-full text-white font-bold text-2xl">
                HILANG <?= $w['selisih'] ?> BOTOL!
            </span>
        </div>
        <?php endforeach; ?>
        <p class="text-center mt-4 text-2xl font-bold">
            🚨 PRODUK ANDA TELAH DICURI! 🚨
        </p>
    </div>
    <?php else: ?>
    <div class="bg-green-100 border-l-8 border-green-500 text-green-800 p-6 mb-6 rounded-lg shadow">
        <div class="flex items-center text-xl">
            <span class="text-4xl mr-4">✅</span>
            <span class="font-bold">Stok gudang dan kasir aman. Tidak ada indikasi pencurian.</span>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Grid: Stok Gudang -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <?php foreach ($produk as $p): ?>
        <div class="bg-white rounded-lg shadow p-6 border-l-8 border-blue-500">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-xl font-bold text-gray-800"><?= $p['nama_produk'] ?></h3>
                    <p class="text-gray-600">Kode: <?= $p['kode_produk'] ?></p>
                </div>
                <span class="text-4xl">🥤</span>
            </div>
            <div class="mt-4">
                <p class="text-sm text-gray-500">Stok Gudang</p>
                <p class="text-4xl font-bold text-blue-600"><?= $p['stok_gudang'] ?></p>
                <p class="text-sm text-gray-500 mt-2">Botol</p>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2">
                <div class="bg-gray-100 p-2 rounded">
                    <p class="text-xs text-gray-600">Harga Beli</p>
                    <p class="font-bold"><?= rupiah($p['harga_beli']) ?></p>
                </div>
                <div class="bg-gray-100 p-2 rounded">
                    <p class="text-xs text-gray-600">Harga Jual</p>
                    <p class="font-bold"><?= rupiah($p['harga_jual']) ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- 3 Kolom Utama -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom 1: Tambah Stok -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-green-700 mb-4 flex items-center">
                <span class="mr-2">📥</span> TAMBAH STOK MASUK
            </h2>
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Produk</label>
                        <select name="produk_id" required class="w-full border rounded-lg p-3">
                            <?php foreach ($produk as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= $p['nama_produk'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Jumlah Botol</label>
                        <input type="number" name="jumlah" min="1" required class="w-full border rounded-lg p-3">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Keterangan</label>
                        <input type="text" name="keterangan" class="w-full border rounded-lg p-3" placeholder="Dari supplier...">
                    </div>
                    <button type="submit" name="tambah_stok" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg text-lg">
                        ➕ TAMBAH STOK
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Kolom 2: Stok Keluar (Rusak/Transfer) -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-yellow-700 mb-4 flex items-center">
                <span class="mr-2">📤</span> STOK KELUAR
            </h2>
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Produk</label>
                        <select name="produk_id" required class="w-full border rounded-lg p-3">
                            <?php foreach ($produk as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= $p['nama_produk'] ?> (Stok: <?= $p['stok_gudang'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Jumlah</label>
                        <input type="number" name="jumlah" min="1" required class="w-full border rounded-lg p-3">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Kondisi</label>
                        <select name="kondisi" id="kondisi" onchange="toggleCabang()" required class="w-full border rounded-lg p-3">
                            <option value="rusak">🔴 Rusak</option>
                            <option value="transfer">🔄 Transfer ke Cabang</option>
                        </select>
                    </div>
                    <div id="cabang_tujuan_div" style="display: none;">
                        <label class="block text-gray-700 font-medium mb-2">Tujuan Cabang</label>
                        <select name="cabang_tujuan" class="w-full border rounded-lg p-3">
                            <?php foreach ($cabang as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Keterangan</label>
                        <textarea name="keterangan" rows="2" class="w-full border rounded-lg p-3"></textarea>
                    </div>
                    <button type="submit" name="stok_keluar" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-3 rounded-lg text-lg">
                        🔻 KURANGI STOK
                    </button>
                </div>
            </form>
            <script>
                function toggleCabang() {
                    var kondisi = document.getElementById('kondisi').value;
                    document.getElementById('cabang_tujuan_div').style.display = kondisi === 'transfer' ? 'block' : 'none';
                }
            </script>
        </div>
        
        <!-- Kolom 3: Pengeluaran -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-red-700 mb-4 flex items-center">
                <span class="mr-2">💸</span> PENGELUARAN
            </h2>
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Nominal</label>
                        <input type="number" name="nominal" min="0" required class="w-full border rounded-lg p-3" placeholder="Rp 0">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Keterangan</label>
                        <input type="text" name="keterangan" required class="w-full border rounded-lg p-3" placeholder="Listrik, air, dll">
                    </div>
                    <button type="submit" name="tambah_pengeluaran" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg text-lg">
                        💰 CATAT PENGELUARAN
                    </button>
                </div>
            </form>
            
            <!-- Riwayat Pengeluaran Hari Ini -->
            <div class="mt-6 pt-6 border-t">
                <h3 class="font-bold text-lg mb-3">📋 Pengeluaran Hari Ini</h3>
                <?php $pengeluaran_hari = query("SELECT * FROM pengeluaran WHERE DATE(created_at) = CURDATE() ORDER BY created_at DESC LIMIT 5"); ?>
                <?php if (count($pengeluaran_hari) > 0): ?>
                    <?php foreach ($pengeluaran_hari as $ph): ?>
                    <div class="flex justify-between items-center bg-gray-50 p-2 rounded mb-2">
                        <span class="text-sm"><?= date('H:i', strtotime($ph['created_at'])) ?> - <?= $ph['keterangan'] ?></span>
                        <span class="font-bold text-red-600"><?= rupiah($ph['nominal']) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">Belum ada pengeluaran</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Baris 2: Stock Opname -->
    <div class="bg-white rounded-lg shadow p-6 mt-6">
        <h2 class="text-2xl font-bold text-purple-700 mb-4 flex items-center">
            <span class="mr-2">📋</span> STOCK OPNAME - HITUNG STOK FISIK
        </h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <form method="POST">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Produk</label>
                            <select name="produk_id" required class="w-full border rounded-lg p-3">
                                <?php foreach ($produk as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= $p['nama_produk'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Stok Fisik</label>
                            <input type="number" name="stok_fisik" min="0" required class="w-full border rounded-lg p-3">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Petugas</label>
                            <input type="text" name="petugas" required class="w-full border rounded-lg p-3" value="<?= $_SESSION['user']['nama'] ?>">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Tanggal</label>
                            <input type="date" name="tanggal" required class="w-full border rounded-lg p-3" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <button type="submit" name="stock_opname" class="mt-4 w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg text-lg">
                        🔍 HITUNG SELISIH
                    </button>
                </form>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-bold text-lg mb-3">📊 Riwayat Stock Opname</h3>
                <?php $so_history = query("SELECT so.*, p.nama_produk FROM stock_opname so JOIN produk p ON so.produk_id = p.id ORDER BY so.created_at DESC LIMIT 10"); ?>
                <?php if (count($so_history) > 0): ?>
                    <div class="space-y-2 max-h-80 overflow-y-auto">
                        <?php foreach ($so_history as $so): ?>
                        <div class="bg-white p-3 rounded shadow-sm <?= $so['status'] == 'HILANG' ? 'border-l-4 border-red-500' : 'border-l-4 border-green-500' ?>">
                            <div class="flex justify-between">
                                <span class="font-bold"><?= $so['nama_produk'] ?></span>
                                <span class="text-sm"><?= date('d/m/Y', strtotime($so['tanggal'])) ?></span>
                            </div>
                            <div class="flex justify-between mt-1">
                                <span>Sistem: <?= $so['stok_sistem'] ?> | Fisik: <?= $so['stok_fisik'] ?></span>
                                <span class="font-bold <?= $so['selisih'] > 0 ? 'text-red-600' : 'text-green-600' ?>">
                                    <?= $so['selisih'] > 0 ? 'HILANG ' . $so['selisih'] : ($so['selisih'] < 0 ? 'LEBIH ' . abs($so['selisih']) : 'SESUAI') ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Petugas: <?= $so['petugas'] ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">Belum ada stock opname</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Tombol Akses Cepat -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
        <a href="stock-opname.php" class="bg-indigo-600 hover:bg-indigo-700 text-white p-4 rounded-lg flex items-center justify-between">
            <span class="font-bold text-lg">📋 LAPORAN STOCK OPNAME</span>
            <span class="text-2xl">→</span>
        </a>
        <a href="stok-masuk.php" class="bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-lg flex items-center justify-between">
            <span class="font-bold text-lg">📦 RIWAYAT STOK MASUK</span>
            <span class="text-2xl">→</span>
        </a>
    </div>
</div>
<?php include '../../includes/layout_footer.php'; ?>