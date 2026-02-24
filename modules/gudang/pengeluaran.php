<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!is_login()) redirect('../../login.php');

if (isset($_GET['dismiss']) && isset($_GET['id'])) {
    $so_id = intval($_GET['id']);
    execute("UPDATE stock_opname SET is_hidden = 1 WHERE id = $so_id");
    header("Location: pengeluaran.php");
    exit;
}
if (isset($_GET['dismiss_so']) && isset($_GET['id'])) {
    $so_id = intval($_GET['id']);
    execute("UPDATE stock_opname SET is_hidden = 1 WHERE id = $so_id");
    header("Location: pengeluaran.php");
    exit;
}

if (isset($_POST['tambah_pengeluaran'])) {
    $nominal = intval($_POST['nominal']);
    $keterangan = escape_string($_POST['keterangan']);
    execute("INSERT INTO pengeluaran (nominal, keterangan) VALUES ($nominal, '$keterangan')");
    $success_pengeluaran = "✅ Pengeluaran dicatat!";
}

$title = 'Pengeluaran - Gudang';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';

$cek_hilang = cek_selisih_stok();
include '../../includes/modal_confirm.php';
?>

    <style>
        .notif-item { position: relative; padding-right: 40px; }
        .close-notif { position: absolute; top: 10px; right: 15px; background: rgba(255,255,255,0.2); border: none; color: white; font-size: 24px; font-weight: bold; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; }
    </style>

    <div class="p-4 lg:p-6">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h1 class="judul text-2xl lg:text-3xl font-bold text-gray-800 flex items-center">
                <span class="mr-3">💸</span> PENGELUARAN OPERASIONAL
            </h1>
            <p class="text-sm text-gray-600 mt-1">👤 <?= $_SESSION['user']['nama'] ?></p>
        </div>

        <?php if (isset($success_pengeluaran)): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4 rounded"><?= $success_pengeluaran ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Form Pengeluaran -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-red-700 mb-4">💰 Catat Pengeluaran</h2>
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
                        <button type="submit" name="tambah_pengeluaran" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg text-lg">💰 CATAT</button>
                    </div>
                </form>
            </div>

            <!-- Riwayat Pengeluaran Hari Ini -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-bold text-lg mb-3">📋 Pengeluaran Hari Ini</h3>
                <?php $pengeluaran_hari = query("SELECT * FROM pengeluaran WHERE DATE(created_at) = CURDATE() ORDER BY created_at DESC LIMIT 10"); ?>
                <?php if (count($pengeluaran_hari) > 0): ?>
                    <div class="space-y-2 max-h-80 overflow-y-auto">
                        <?php foreach ($pengeluaran_hari as $ph): ?>
                            <div class="flex justify-between items-center bg-white p-3 rounded shadow-sm">
                                <div>
                                    <p class="font-medium"><?= $ph['keterangan'] ?></p>
                                    <p class="text-xs text-gray-500"><?= date('H:i', strtotime($ph['created_at'])) ?></p>
                                </div>
                                <span class="font-bold text-red-600"><?= rupiah($ph['nominal']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">Belum ada pengeluaran</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include '../../includes/layout_footer.php'; ?>