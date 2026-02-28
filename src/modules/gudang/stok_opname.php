<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!is_login()) redirect('../../login.php');

if (isset($_GET['dismiss']) && isset($_GET['id'])) {
    $so_id = intval($_GET['id']);
    execute("UPDATE stock_opname SET is_hidden = 1 WHERE id = $so_id");
    header("Location: stok_opname.php");
    exit;
}
if (isset($_GET['dismiss_so']) && isset($_GET['id'])) {
    $so_id = intval($_GET['id']);
    execute("UPDATE stock_opname SET is_hidden = 1 WHERE id = $so_id");
    header("Location: stok_opname.php");
    exit;
}

if (isset($_POST['stock_opname'])) {
    $produk_id = $_POST['produk_id'];
    $stok_fisik = intval($_POST['stok_fisik']);
    $petugas = escape_string($_POST['petugas']);
    $tanggal = $_POST['tanggal'];

    $stok_sistem = get_stok_gudang($produk_id);
    $selisih = $stok_sistem - $stok_fisik;
    $status = $selisih > 0 ? 'HILANG' : ($selisih < 0 ? 'LEBIH' : 'SESUAI');

    execute("INSERT INTO stock_opname (produk_id, stok_sistem, stok_fisik, selisih, status, petugas, tanggal, is_hidden)
             VALUES ($produk_id, $stok_sistem, $stok_fisik, $selisih, '$status', '$petugas', '$tanggal', 0)");
    $success_so = "✅ Stock Opname selesai! Selisih: $selisih botol ($status)";
}

$title = 'Stock Opname - Gudang';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';

$produk = get_produk();
$cek_hilang = cek_selisih_stok();
include '../../includes/modal_confirm.php';
?>

    <div class="p-4 lg:p-6">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h1 class="judul text-2xl lg:text-3xl font-bold text-gray-800 flex items-center">
                <span class="mr-3">📋</span> STOCK OPNAME
            </h1>
            <p class="text-sm text-gray-600 mt-1">👤 <?= $_SESSION['user']['nama'] ?></p>
        </div>

        <!-- Notifikasi selisih -->
        <?php if (count($cek_hilang) > 0): ?>
            <div class="bg-red-600 text-white p-4 lg:p-6 rounded-lg mb-4 shadow-lg border-2 border-red-800">
                <div class="flex items-center text-xl lg:text-2xl font-bold mb-4">
                    <span class="text-4xl lg:text-5xl mr-4">⚠️</span>
                    <span>PERINGATAN! DITEMUKAN SELISIH STOK (7 HARI TERAKHIR)</span>
                </div>
                <?php foreach ($cek_hilang as $w): ?>
                    <div class="bg-red-700 p-3 lg:p-4 mb-3 rounded notif-item">
                        <a href="?dismiss=1&id=<?= $w['id'] ?>" class="close-notif" onclick="return confirm('Tutup?')">&times;</a>
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-bold"><?= $w['produk'] ?></span>
                            <span class="text-xs"><?= date('d/m/Y', strtotime($w['tanggal'])) ?></span>
                        </div>
                        <div class="flex justify-between items-center flex-wrap gap-2">
                            <span>Sistem: <?= $w['stok_sistem'] ?> | Fisik: <?= $w['stok_fisik'] ?></span>
                            <span class="bg-red-800 px-3 py-1 rounded-full text-white font-bold">HILANG <?= $w['selisih'] ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 mb-4 rounded-lg">
                <span class="font-bold">✅ Tidak ada selisih stok 7 hari terakhir.</span>
            </div>
        <?php endif; ?>

        <?php if (isset($success_so)): ?>
            <div class="bg-purple-100 border-l-4 border-purple-500 text-purple-700 p-4 mb-4 rounded"><?= $success_so ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Form Opname -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-purple-700 mb-4">➕ Input Stok Fisik</h2>
                <form method="POST">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Produk</label>
                            <label>
                                <select name="produk_id" required class="w-full border rounded-lg p-3">
                                    <?php foreach ($produk as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= $p['nama_produk'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Stok Fisik</label>
                            <label>
                                <input type="number" name="stok_fisik" min="0" required class="w-full border rounded-lg p-3">
                            </label>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Petugas</label>
                            <label>
                                <input type="text" name="petugas" required class="w-full border rounded-lg p-3" value="<?= $_SESSION['user']['nama'] ?>">
                            </label>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Tanggal</label>
                            <label>
                                <input type="date" name="tanggal" required class="w-full border rounded-lg p-3" value="<?= date('Y-m-d') ?>">
                            </label>
                        </div>
                    </div>
                    <button type="submit" name="stock_opname" class="mt-4 w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg text-lg">🔍 HITUNG SELISIH</button>
                </form>
            </div>

            <!-- Riwayat Opname -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-bold text-lg mb-3">📊 Riwayat Stock Opname</h3>
                <?php
                $so_history = query("SELECT so.*, p.nama_produk FROM stock_opname so JOIN produk p ON so.produk_id = p.id WHERE so.is_cancelled = 0 ORDER BY so.created_at DESC LIMIT 10");
                ?>
                <?php if (count($so_history) > 0): ?>
                    <div class="space-y-2 max-h-80 overflow-y-auto">
                        <?php foreach ($so_history as $so): ?>
                            <div class="bg-white p-3 rounded shadow-sm relative <?= $so['status'] == 'HILANG' ? 'border-l-4 border-red-500' : 'border-l-4 border-green-500' ?>">
                                <!-- Tombol X -->
                                <a href="?dismiss_so=1&id=<?= $so['id'] ?>" class="close-notif-so" onclick="return confirm('Hapus riwayat ini?')" title="Hapus">&times;</a>

                                <!-- Nama Produk -->
                                <div class="font-bold pr-6"><?= $so['nama_produk'] ?></div>

                                <!-- Info Stok -->
                                <div class="flex justify-between mt-1">
                                    <span class="text-sm">Sistem: <?= $so['stok_sistem'] ?> | Fisik: <?= $so['stok_fisik'] ?></span>
                                    <span class="font-bold text-sm <?= $so['selisih'] > 0 ? 'text-red-600' : 'text-green-600' ?>">
                            <?= $so['selisih'] > 0 ? 'HILANG ' . $so['selisih'] : ($so['selisih'] < 0 ? 'LEBIH ' . abs($so['selisih']) : 'SESUAI') ?>
                        </span>
                                </div>

                                <!-- TANGGAL SAJA (PETUGAS DIHAPUS) -->
                                <div class="text-xs text-gray-500 mt-2">
                                    📅 <?= date('d/m/Y', strtotime($so['tanggal'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">Belum ada stock opname</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include '../../includes/layout_footer.php'; ?>