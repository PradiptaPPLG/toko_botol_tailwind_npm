<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php'; // <== INI WAJIB!
if (!is_login()) redirect('../../login.php');
if (is_admin()) {
    $title = 'Transaksi Kasir';
} else {
    $title = 'Halaman Kasir - ' . $_SESSION['user']['nama'];
}

include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';

$cabang_id = is_admin() ? ($_GET['cabang'] ?? 1) : $_SESSION['user']['cabang_id'];
$nama_cabang = query("SELECT nama_cabang FROM cabang WHERE id = $cabang_id")[0]['nama_cabang'];
$produk = get_produk();

// Proses transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_transaksi'])) {
    $produk_id = $_POST['produk_id'];
    $tipe = $_POST['tipe'];
    $jumlah = intval($_POST['jumlah']);
    $harga_tawar = !empty($_POST['harga_tawar']) ? intval($_POST['harga_tawar']) : null;
    
    $data_produk = query("SELECT * FROM produk WHERE id = $produk_id")[0];
    $harga_satuan = 0;
    $selisih = null;
    
    if ($tipe === 'pembeli') {
        if ($_POST['satuan'] === 'dus') {
            $harga_satuan = $data_produk['harga_dus'];
            $jumlah_botol = $jumlah * 12; // asumsi 1 dus = 12 botol
        } else {
            $harga_satuan = $data_produk['harga_jual'];
            $jumlah_botol = $jumlah;
        }
    } else {
        $harga_satuan = $harga_tawar;
        $selisih = ($data_produk['harga_jual'] * $jumlah) - ($harga_tawar * $jumlah);
        $jumlah_botol = $jumlah;
    }
    
    $total_harga = $harga_satuan * $jumlah;
    $invoice = generate_invoice();
    
    // Kurangi stok cabang
    $stok_cabang = get_stok_cabang($produk_id, $cabang_id);
    if ($stok_cabang >= $jumlah_botol) {
        execute("UPDATE stok_cabang SET stok = stok - $jumlah_botol WHERE produk_id = $produk_id AND cabang_id = $cabang_id");
        
        // Simpan transaksi
        $nama_kasir = $_SESSION['user']['nama'];
        $session_id = is_admin() ? 'NULL' : $_SESSION['user']['id'];
        
        $sql = "INSERT INTO transaksi (no_invoice, produk_id, cabang_id, session_kasir_id, nama_kasir, tipe, jumlah, satuan, harga_satuan, harga_tawar, selisih, total_harga) 
                VALUES ('$invoice', $produk_id, $cabang_id, $session_id, '$nama_kasir', '$tipe', $jumlah, '{$_POST['satuan']}', $harga_satuan, " . ($harga_tawar ?? 'NULL') . ", " . ($selisih ?? 'NULL') . ", $total_harga)";
        
        if (execute($sql)) {
            $success = "Transaksi berhasil!";
        }
    } else {
        $error = "Stok tidak mencukupi! Sisa stok: $stok_cabang botol";
    }
}

// Rekap hari ini
$rekap = query("
    SELECT 
        COUNT(*) as total_transaksi,
        SUM(CASE WHEN tipe = 'pembeli' THEN total_harga ELSE 0 END) as total_penjualan,
        SUM(CASE WHEN tipe = 'penjual' THEN total_harga ELSE 0 END) as total_pembelian
    FROM transaksi 
    WHERE cabang_id = $cabang_id AND DATE(created_at) = CURDATE()
")[0];

$pengeluaran = query("SELECT SUM(nominal) as total FROM pengeluaran WHERE DATE(created_at) = CURDATE()")[0]['total'] ?? 0;

// Riwayat transaksi hari ini
$riwayat = query("
    SELECT t.*, p.nama_produk 
    FROM transaksi t 
    JOIN produk p ON t.produk_id = p.id 
    WHERE t.cabang_id = $cabang_id AND DATE(t.created_at) = CURDATE() 
    ORDER BY t.created_at DESC 
    LIMIT 20
");
?>
<div class="p-6">
    <!-- Header Kasir -->
    <div class="bg-white rounded-lg shadow p-4 mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                <span class="mr-3">🛒</span> TRANSAKSI BOTOL
            </h1>
            <p class="text-gray-600 mt-1">
                📍 Cabang: <span class="font-bold text-blue-600"><?= $nama_cabang ?></span> | 
                👤 Kasir: <span class="font-bold"><?= $_SESSION['user']['nama'] ?></span>
            </p>
        </div>
        <?php if (is_admin()): ?>
        <div>
            <select onchange="window.location.href='?cabang='+this.value" class="border rounded-lg p-2 text-lg">
                <?php foreach (get_cabang() as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id'] == $cabang_id ? 'selected' : '' ?>>
                    🏢 <?= $c['nama_cabang'] ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Rekap Hari Ini -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg shadow p-4">
            <p class="text-sm opacity-90">💰 Total Penjualan Hari Ini</p>
            <p class="text-3xl font-bold"><?= rupiah($rekap['total_penjualan'] ?? 0) ?></p>
            <p class="text-xs mt-1"><?= ($rekap['total_transaksi'] ?? 0) ?> transaksi</p>
        </div>
        <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-lg shadow p-4">
            <p class="text-sm opacity-90">💸 Total Pembelian (Penjual)</p>
            <p class="text-3xl font-bold"><?= rupiah($rekap['total_pembelian'] ?? 0) ?></p>
        </div>
        <div class="bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg shadow p-4">
            <p class="text-sm opacity-90">📉 Pengeluaran Hari Ini</p>
            <p class="text-3xl font-bold"><?= rupiah($pengeluaran) ?></p>
        </div>
    </div>
    
    <!-- Form Transaksi (SIMPEL, langsung muncul) -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <?php if (isset($success)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
            ✅ <?= $success ?>
        </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
            ❌ <?= $error ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Kolom Kiri: Pembeli -->
                <div class="border-2 border-blue-200 rounded-lg p-4">
                    <h2 class="text-xl font-bold text-blue-700 mb-4 flex items-center">
                        <span class="mr-2">🛍️</span> PEMBELI (Harga Pas)
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Produk</label>
                            <select name="produk_id" required class="w-full border rounded-lg p-3 text-lg">
                                <option value="">-- Pilih Produk --</option>
                                <?php foreach ($produk as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= $p['nama_produk'] ?> - Stok: <?= get_stok_cabang($p['id'], $cabang_id) ?> botol
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Satuan</label>
                                <select name="satuan" class="w-full border rounded-lg p-3 text-lg">
                                    <option value="botol">🥤 Botol</option>
                                    <option value="dus">📦 Dus</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Jumlah</label>
                                <input type="number" name="jumlah" min="1" required class="w-full border rounded-lg p-3 text-lg">
                            </div>
                        </div>
                        <input type="hidden" name="tipe" value="pembeli">
                        <button type="submit" name="simpan_transaksi" class="w-full btn-primary text-white font-bold py-3 px-4 rounded-lg text-lg">
                            💵 BAYAR
                        </button>
                    </div>
                </div>
                
                <!-- Kolom Kanan: Penjual -->
                <div class="border-2 border-green-200 rounded-lg p-4">
                    <h2 class="text-xl font-bold text-green-700 mb-4 flex items-center">
                        <span class="mr-2">💼</span> PENJUAL (Harga Tawar)
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Produk</label>
                            <select name="produk_id" required class="w-full border rounded-lg p-3 text-lg">
                                <option value="">-- Pilih Produk --</option>
                                <?php foreach ($produk as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= $p['nama_produk'] ?> - Harga Jual: <?= rupiah($p['harga_jual']) ?>/botol
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Jumlah Botol</label>
                                <input type="number" name="jumlah" min="1" required class="w-full border rounded-lg p-3 text-lg">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Harga Tawar</label>
                                <input type="number" name="harga_tawar" required class="w-full border rounded-lg p-3 text-lg">
                            </div>
                        </div>
                        <input type="hidden" name="tipe" value="penjual">
                        <input type="hidden" name="satuan" value="botol">
                        <button type="submit" name="simpan_transaksi" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg text-lg">
                            🤝 BELI DARI PENJUAL
                        </button>
                        <p class="text-xs text-gray-500 mt-2">
                            * Selisih keuntungan akan dihitung otomatis
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Riwayat Transaksi Hari Ini -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gray-50 p-4 border-b">
            <h2 class="text-xl font-bold text-gray-800 flex items-center">
                <span class="mr-2">📋</span> RIWAYAT TRANSAKSI HARI INI
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Waktu</th>
                        <th class="p-3 text-left">Invoice</th>
                        <th class="p-3 text-left">Produk</th>
                        <th class="p-3 text-left">Tipe</th>
                        <th class="p-3 text-left">Jumlah</th>
                        <th class="p-3 text-left">Harga</th>
                        <th class="p-3 text-left">Total</th>
                        <th class="p-3 text-left">Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($riwayat) > 0): ?>
                        <?php foreach ($riwayat as $r): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3"><?= date('H:i:s', strtotime($r['created_at'])) ?></td>
                            <td class="p-3 font-mono text-sm"><?= $r['no_invoice'] ?></td>
                            <td class="p-3"><?= $r['nama_produk'] ?></td>
                            <td class="p-3">
                                <?php if ($r['tipe'] == 'pembeli'): ?>
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">PEMBELI</span>
                                <?php else: ?>
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">PENJUAL</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3"><?= $r['jumlah'] ?> <?= $r['satuan'] ?></td>
                            <td class="p-3"><?= rupiah($r['harga_satuan']) ?></td>
                            <td class="p-3 font-bold"><?= rupiah($r['total_harga']) ?></td>
                            <td class="p-3 <?= $r['selisih'] > 0 ? 'text-green-600' : '' ?>">
                                <?= $r['selisih'] ? rupiah($r['selisih']) : '-' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-500">
                                🕊️ Belum ada transaksi hari ini
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../../includes/layout_footer.php'; ?>