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
        $botol_perdus = intval($_POST['botol_perdus'] ?? 12);
        if ($botol_perdus < 1) $botol_perdus = 12;
        $satuan = 'botol';
        $stok_gudang = 0;
 
         $sql = "INSERT INTO produk (kode_produk, nama_produk, satuan, botol_perdus, harga_beli, stok_gudang)
                 VALUES ('$kode_produk', '$nama_produk', '$satuan', $botol_perdus, 0, $stok_gudang)";

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

    if (isset($_POST['edit_produk'])) {
        $id = intval($_POST['id']);
        $nama_produk = escape_string($_POST['nama_produk']);
        $botol_perdus = intval($_POST['botol_perdus'] ?? 12);
        if ($botol_perdus < 1) $botol_perdus = 12;

        $sql = "UPDATE produk SET nama_produk='$nama_produk', botol_perdus=$botol_perdus WHERE id=$id";

        if (execute($sql)) {
            $success = "Produk berhasil diupdate!";
        } else {
            $error = "Gagal mengupdate produk!";
        }
    }
}

// Soft Delete handler (SAFE - No DELETE query!)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $produk_data = query("SELECT nama_produk FROM produk WHERE id = $id AND status = 'active'");

    if (!empty($produk_data)) {
        $produk_nama = $produk_data[0]['nama_produk'];

        // Softly delete: Update status instead of DELETE
        $sql = "UPDATE produk SET status = 'deleted', deleted_at = NOW() WHERE id = $id";

        if (execute($sql)) {
            $success = "Produk '$produk_nama' berhasil dinonaktifkan!";
        } else {
            $error = "Gagal menghapus produk!";
        }
    } else {
        $error = "Produk tidak ditemukan atau sudah dihapus!";
    }
}

// Restore handler
if (isset($_GET['restore'])) {
    $id = intval($_GET['restore']);
    $sql = "UPDATE produk SET status = 'active', deleted_at = NULL WHERE id = $id";
    if (execute($sql)) {
        $success = "Produk berhasil diaktifkan kembali!";
    } else {
        $error = "Gagal mengaktifkan produk!";
    }
}

$produk_aktif = get_produk();
$produk_nonaktif = query("SELECT * FROM produk WHERE status = 'deleted' ORDER BY deleted_at DESC");

// Include confirmation modal
include '../../includes/modal_confirm.php';
?>
<div class="p-6">
    <h1 class="judul text-3xl font-bold text-gray-800 mb-6 flex items-center">
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
                    <label class="block text-gray-700 font-bold mb-2 text-lg">Nama Produk Botol</label>
                    <label>
                        <input type="text" name="nama_produk" required class="w-full border-2 border-gray-300 rounded-lg p-4 text-lg" placeholder="Contoh: Botol Kaca 330ml">
                    </label>
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-lg">Jumlah Botol per Dus</label>
                    <label>
                        <input type="number" name="botol_perdus" min="1" value="12" required class="w-full border-2 border-gray-300 rounded-lg p-4 text-lg" placeholder="12">
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Berapa botol dalam 1 dus untuk produk ini</p>
                </div>

<div class="space-y-4">
    💡 Harga beli diisi saat mencatat <strong>Stok Masuk</strong> di menu Gudang. Harga jual ditentukan saat transaksi.
</div>

                <button type="submit" name="tambah_produk" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-4 rounded-lg text-xl mt-4">
                    ✅ TAMBAH PRODUK
                </button>
            </form>
        </div>

        <!-- Daftar Produk Tabbed -->
        <div class="bg-white rounded-lg shadow flex flex-col overflow-hidden product-list-container">
            <!-- Tabs Header -->
            <div class="flex border-b">
                <button onclick="switchTab('active')" id="tabActive" class="flex-1 py-4 font-bold text-blue-600 border-b-4 border-blue-600 transition-all">
                    📦 PRODUK AKTIF (<?= count($produk_aktif) ?>)
                </button>
                <button onclick="switchTab('deleted')" id="tabDeleted" class="flex-1 py-4 font-bold text-gray-400 hover:text-red-500 transition-all">
                    🗑️ NONAKTIF (<?= count($produk_nonaktif) ?>)
                </button>
            </div>

            <div class="p-6 overflow-y-auto pr-2">
                <!-- Tab Active -->
                <div id="contentActive" class="space-y-3">
                    <?php if (empty($produk_aktif)): ?>
                        <p class="text-center text-gray-400 py-10 italic">Belum ada produk aktif</p>
                    <?php endif; ?>
                    <?php foreach ($produk_aktif as $p): ?>
                    <div class="bg-gray-50 p-4 rounded-lg border-l-8 border-blue-500 hover:bg-white transition-all shadow-sm">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="font-bold text-lg text-gray-800"><?= $p['nama_produk'] ?></p>
                                <p class="text-xs text-gray-500 font-mono">CODE: <?= $p['kode_produk'] ?></p>
                                <div class="grid grid-cols-2 gap-2 mt-3">
                                    <div class="text-[10px] uppercase font-bold text-gray-400">Stok Gudang: <span class="text-blue-600 text-sm block font-black"><?= number_format($p['stok_gudang'] ?? 0, 0, ',', '.') ?></span></div>
                                    <div class="text-[10px] uppercase font-bold text-gray-400">Botol/Dus: <span class="text-purple-600 text-sm block font-black"><?= $p['botol_perdus'] ?? 12 ?></span></div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2 ml-4">
                                <button onclick="editProduct(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_produk']) ?>', <?= $p['botol_perdus'] ?? 12 ?>)"
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-2 rounded-lg text-xs font-bold shadow-sm transition-all">
                                    ✏️ Edit
                                </button>
                                <button onclick="deleteProduct(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_produk']) ?>')"
                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-xs font-bold shadow-sm transition-all">
                                    🗑️ Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Tab Deleted -->
                <div id="contentDeleted" class="space-y-3 hidden">
                    <?php if (empty($produk_nonaktif)): ?>
                        <p class="text-center text-gray-400 py-10 italic">Tidak ada produk nonaktif</p>
                    <?php endif; ?>
                    <?php foreach ($produk_nonaktif as $p): ?>
                    <div class="bg-red-50 p-4 rounded-lg border-l-8 border-gray-400 opacity-80 grayscale-[0.5] hover:grayscale-0 transition-all">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="font-bold text-lg text-gray-600 line-through"><?= $p['nama_produk'] ?></p>
                                <p class="text-[10px] text-red-400 font-bold uppercase">Dihapus pada: <?= date('d/m/Y H:i', strtotime($p['deleted_at'])) ?></p>
                            </div>
                            <div class="ml-4">
                                <button onclick="restoreProduct(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_produk']) ?>')"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-xs font-black shadow-md transition-all flex items-center gap-1">
                                    <span>🔄</span> AKTIFKAN
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-9999 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-2xl p-6 max-w-2xl w-full mx-4">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">✏️ Edit Produk</h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="edit_produk" value="1">
            <input type="hidden" name="id" id="edit_id">

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Nama Produk</label>
                <label for="edit_nama"></label><input type="text" name="nama_produk" id="edit_nama" required class="w-full border-2 border-gray-300 rounded-lg p-3 text-lg">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Jumlah Botol per Dus</label>
                <label for="edit_botol_perdus"></label><input type="number" name="botol_perdus" id="edit_botol_perdus" min="1" required class="w-full border-2 border-gray-300 rounded-lg p-3 text-lg">
            </div>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 text-sm text-yellow-800 rounded mb-4">
                💡 Harga jual ditentukan secara manual saat melakukan transaksi.
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeEditModal()"
                        class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-4 rounded-lg">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg">
                    💾 Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
async function deleteProduct(id, nama) {
    const confirmed = await confirmDelete(`Hapus produk "${nama}"?\n\nProduk akan dipindahkan ke daftar Nonaktif.`);
    if (confirmed) {
        window.location.href = '?delete=' + id;
    }
}

async function restoreProduct(id, nama) {
    const confirmed = await confirmDelete(`Aktifkan kembali produk "${nama}"?`);
    if (confirmed) {
        window.location.href = '?restore=' + id;
    }
}

function switchTab(tab) {
    const tabActive = document.getElementById('tabActive');
    const tabDeleted = document.getElementById('tabDeleted');
    const contentActive = document.getElementById('contentActive');
    const contentDeleted = document.getElementById('contentDeleted');

    if (tab === 'active') {
        tabActive.classList.add('text-blue-600', 'border-blue-600', 'border-b-4');
        tabActive.classList.remove('text-gray-400');
        tabDeleted.classList.remove('text-red-500', 'border-red-500', 'border-b-4');
        tabDeleted.classList.add('text-gray-400');
        contentActive.classList.remove('hidden');
        contentDeleted.classList.add('hidden');
    } else {
        tabDeleted.classList.add('text-red-500', 'border-red-500', 'border-b-4');
        tabDeleted.classList.remove('text-gray-400');
        tabActive.classList.remove('text-blue-600', 'border-blue-600', 'border-b-4');
        tabActive.classList.add('text-gray-400');
        contentDeleted.classList.remove('hidden');
        contentActive.classList.add('hidden');
    }
}

function editProduct(id, nama, botolPerdus) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_botol_perdus').value = botolPerdus;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Close modal on outside click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php include '../../includes/layout_footer.php'; ?>