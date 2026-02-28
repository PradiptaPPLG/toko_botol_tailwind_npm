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

// Tambah pengeluaran
if (isset($_POST['tambah_pengeluaran'])) {
    $nominal = intval(str_replace('.', '', $_POST['nominal']));
    $keterangan = escape_string($_POST['keterangan']);
    execute("INSERT INTO pengeluaran (nominal, keterangan) VALUES ($nominal, '$keterangan')");
    $success_pengeluaran = "✅ Pengeluaran dicatat!";
}

// Edit pengeluaran
if (isset($_POST['edit_pengeluaran'])) {
    $id = intval($_POST['id']);
    $nominal = intval(str_replace('.', '', $_POST['nominal']));
    $keterangan = escape_string($_POST['keterangan']);
    execute("UPDATE pengeluaran SET nominal = $nominal, keterangan = '$keterangan' WHERE id = $id AND deleted_at IS NULL");
    $success_pengeluaran = "✅ Pengeluaran berhasil diperbarui!";
}

// Soft delete pengeluaran
if (isset($_GET['hapus_pengeluaran'])) {
    $id = intval($_GET['hapus_pengeluaran']);
    execute("UPDATE pengeluaran SET deleted_at = NOW() WHERE id = $id AND deleted_at IS NULL");
    header("Location: pengeluaran.php");
    exit;
}

$title = 'Pengeluaran - Gudang';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';

$cek_hilang = cek_selisih_stok();
include '../../includes/modal_confirm.php';
?>

    <div class="p-4 lg:p-6">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h1 class="judul text-fluid-2xl lg:text-fluid-3xl font-bold text-gray-800 flex items-center">
                <span class="mr-3">💸</span> PENGELUARAN OPERASIONAL
            </h1>
            <p class="text-fluid-sm text-gray-600 mt-1">👤 <?= $_SESSION['user']['nama'] ?></p>
        </div>

        <?php if (isset($success_pengeluaran)): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4 rounded"><?= $success_pengeluaran ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Form Pengeluaran -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-fluid-xl font-bold text-red-700 mb-4">💰 Catat Pengeluaran</h2>
                <form method="POST">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Nominal</label>
                            <label>
                                <input type="text" name="nominal" inputmode="numeric" required class="w-full border rounded-lg p-3 format-number" placeholder="Rp 0">
                            </label>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Keterangan</label>
                            <label>
                                <input type="text" name="keterangan" required class="w-full border rounded-lg p-3" placeholder="Listrik, air, dll">
                            </label>
                        </div>
                        <button type="submit" name="tambah_pengeluaran" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg text-fluid-lg">💰 CATAT</button>
                    </div>
                </form>
            </div>

            <!-- Riwayat Pengeluaran Hari Ini -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-bold text-fluid-lg mb-3">📋 Pengeluaran Hari Ini</h3>
                <?php $pengeluaran_hari = query("SELECT * FROM pengeluaran WHERE DATE(created_at) = CURDATE() AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 10"); ?>
                <?php if (count($pengeluaran_hari) > 0): ?>
                    <div class="space-y-2 max-h-80 overflow-y-auto">
                        <?php foreach ($pengeluaran_hari as $ph): ?>
                            <div class="flex justify-between items-center bg-white p-3 rounded shadow-sm">
                                <div class="flex-1">
                                    <p class="font-medium"><?= $ph['keterangan'] ?></p>
                                    <p class="text-fluid-xs text-gray-500"><?= date('H:i', strtotime($ph['created_at'])) ?></p>
                                </div>
                                <span class="font-bold text-red-600 mr-3"><?= rupiah($ph['nominal']) ?></span>
                                <div class="flex gap-1">
                                    <button onclick="editPengeluaran(<?= $ph['id'] ?>, '<?= htmlspecialchars($ph['keterangan']) ?>', <?= $ph['nominal'] ?>)"
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-fluid-xs font-bold">✏️</button>
                                    <button onclick="hapusPengeluaran(<?= $ph['id'] ?>, '<?= htmlspecialchars($ph['keterangan']) ?>')"
                                            class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-fluid-xs font-bold">🗑️</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">Belum ada pengeluaran</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<!-- Edit Pengeluaran Modal -->
<div id="editPengeluaranModal" class="fixed inset-0 bg-transparent z-[9999] flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-2xl p-6 max-w-md w-full mx-4">
        <h3 class="text-fluid-xl font-bold text-gray-800 mb-4">✏️ Edit Pengeluaran</h3>
        <form method="POST" id="editPengeluaranForm">
            <input type="hidden" name="edit_pengeluaran" value="1">
            <input type="hidden" name="id" id="edit_peng_id">
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Nominal</label>
                <input type="text" name="nominal" id="edit_peng_nominal" inputmode="numeric" required class="w-full border-2 border-gray-300 rounded-lg p-3 text-fluid-lg format-number">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Keterangan</label>
                <input type="text" name="keterangan" id="edit_peng_keterangan" required class="w-full border-2 border-gray-300 rounded-lg p-3 text-fluid-lg">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeEditPengeluaranModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-4 rounded-lg">Batal</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editPengeluaran(id, keterangan, nominal) {
    document.getElementById('edit_peng_id').value = id;
    document.getElementById('edit_peng_keterangan').value = keterangan;
    const nominalInput = document.getElementById('edit_peng_nominal');
    nominalInput.value = formatThousand(nominal);
    document.getElementById('editPengeluaranModal').classList.remove('hidden');
}

function closeEditPengeluaranModal() {
    document.getElementById('editPengeluaranModal').classList.add('hidden');
}

document.getElementById('editPengeluaranModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditPengeluaranModal();
});

async function hapusPengeluaran(id, keterangan) {
    const confirmed = await confirmDelete(`Hapus pengeluaran "${keterangan}"?`);
    if (confirmed) {
        window.location.href = '?hapus_pengeluaran=' + id;
    }
}
</script>

<?php include '../../includes/layout_footer.php'; ?>
