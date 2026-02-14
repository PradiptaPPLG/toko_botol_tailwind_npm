<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php'; // <=== TAMBAHKAN!
if (!is_login() || !is_admin()) redirect('../../login.php');

$title = 'Tambah Produk Botol';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tambah_produk'])) {
        $kode_produk = 'BTL' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $nama_produk = escape_string($_POST['nama_produk']);
        $satuan = 'botol';
        $harga_beli = intval($_POST['harga_beli']);
        $harga_jual = intval($_POST['harga_jual']);
        $harga_dus = intval($_POST['harga_dus']);
        $stok_gudang = intval($_POST['stok_gudang']);
        
        $sql = "INSERT INTO produk (kode_produk, nama_produk, satuan, harga_beli, harga_jual, harga_dus, stok_gudang) 
                VALUES ('$kode_produk', '$nama_produk', '$satuan', $harga_beli, $harga_jual, $harga_dus, $stok_gudang)";
        
        if (execute($sql)) {
            $produk_id = last_insert_id();
            
            // Tambah stok awal ke semua cabang
            foreach (get_cabang() as $cabang) {
                execute("INSERT INTO stok_cabang (produk_id, cabang_id, stok) VALUES ($produk_id, {$cabang['id']}, 0)");
            }
            
            $success = "Produk berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan produk!";
        }
    }
}

$produk = get_produk();
?>
<div class="p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="mr-3">➕</span> TAMBAH PRODUK BOTOL BARU
    </h1>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Form Tambah Produk -->
        <div class="bg-white rounded-lg shadow p-8">
            <?php if (isset($success)): ?>
            <div class="bg-green-100 border-l-8 border-green-500 text-green-800 p-4 mb-6 text-lg font-bold">
                ✅ <?= $success ?>
            </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-8 border-red-500 text-red-800 p-4 mb-6 text-lg font-bold">
                ❌ <?= $error ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-lg">🥤 Nama Produk Botol</label>
                    <input type="text" name="nama_produk" required class="w-full border-2 border-gray-300 rounded-lg p-4 text-lg" placeholder="Contoh: Botol Kaca 330ml">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2 text-lg">💰 Harga Beli</label>
                        <input type="number" name="harga_beli" required class="w-full border-2 border-gray-300 rounded-lg p-4 text-lg" placeholder="Rp">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2 text-lg">💵 Harga Jual</label>
                        <input type="number" name="harga_jual" required class="w-full border-2 border-gray-300 rounded-lg p-4 text-lg" placeholder="Rp">
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-lg">📦 Harga Per Dus</label>
                    <input type="number" name="harga_dus" required class="w-full border-2 border-gray-300 rounded-lg p-4 text-lg" placeholder="Rp (contoh: 72000)">
                    <p class="text-sm text-gray-500 mt-2">* Harga untuk 1 dus (isi 12 botol)</p>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-lg">🏚️ Stok Awal Gudang</label>
                    <input type="number" name="stok_gudang" value="0" min="0" class="w-full border-2 border-gray-300 rounded-lg p-4 text-lg">
                </div>
                
                <button type="submit" name="tambah_produk" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-4 rounded-lg text-xl mt-4">
                    ✅ TAMBAH PRODUK
                </button>
            </form>
        </div>
        
        <!-- Daftar Produk -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="mr-2">📋</span> DAFTAR PRODUK BOTOL
            </h2>
            <div class="space-y-3">
                <?php foreach ($produk as $p): ?>
                <div class="bg-gray-50 p-4 rounded-lg border-l-8 border-blue-500">
                    <div class="flex justify-between">
                        <div>
                            <p class="font-bold text-lg"><?= $p['nama_produk'] ?></p>
                            <p class="text-sm text-gray-600">Kode: <?= $p['kode_produk'] ?></p>
                        </div>
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">Stok: <?= $p['stok_gudang'] ?></span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-2 text-sm">
                        <div>Beli: <?= rupiah($p['harga_beli']) ?></div>
                        <div>Jual: <?= rupiah($p['harga_jual']) ?></div>
                        <div>Dus: <?= rupiah($p['harga_dus']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/layout_footer.php'; ?>