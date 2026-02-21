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
        $stok_gudang = 0; // Always 0 for new products

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

    if (isset($_POST['edit_produk'])) {
        $id = intval($_POST['id']);
        $nama_produk = escape_string($_POST['nama_produk']);
        $harga_beli = intval($_POST['harga_beli']);
        $harga_jual = intval($_POST['harga_jual']);
        $harga_dus = intval($_POST['harga_dus']);

        $sql = "UPDATE produk SET nama_produk='$nama_produk', harga_beli=$harga_beli, harga_jual=$harga_jual, harga_dus=$harga_dus WHERE id=$id";

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
            $success = "Produk '$produk_nama' berhasil dihapus (soft delete)!";
        } else {
            $error = "Gagal menghapus produk!";
        }
    } else {
        $error = "Produk tidak ditemukan atau sudah dihapus!";
    }
}

$produk = get_produk();

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
                    <label class="block text-gray-700 font-bold mb-2 text-lg">🥤 Nama Produk Botol</label>
                    <label>
                        <input type="text" name="nama_produk" required class="w-full border-2 border-gray-300 rounded-lg p-4 text-lg" placeholder="Contoh: Botol Kaca 330ml">
                    </label>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2 text-lg">💰 Harga Beli</label>
                        <label>
                            <input type="number" name="harga_beli" required class="w-full border-2 border-gray-300 rounded-lg p-4 text-lg" placeholder="Rp">
                        </label>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2 text-lg">💵 Harga Jual</label>
                        <label>
                            <input type="number" name="harga_jual" required class="w-full border-2 border-gray-300 rounded-lg p-4 text-lg" placeholder="Rp">
                        </label>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-lg">📦 Harga Per Dus</label>
                    <label>
                        <input type="number" name="harga_dus" required class="w-full border-2 border-gray-300 rounded-lg p-4 text-lg" placeholder="Rp (contoh: 72000)">
                    </label>
                    <p class="text-sm text-gray-500 mt-2">* Harga untuk 1 dus (isi 12 botol)</p>
                </div>

                <button type="submit" name="tambah_produk" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-4 rounded-lg text-xl mt-4">
                    ✅ TAMBAH PRODUK
                </button>
            </form>
        </div>
        
        <!-- Daftar Produk -->
        <div class="bg-white rounded-lg shadow p-6 flex flex-col" style="max-height: 700px;">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="mr-2">📋</span> DAFTAR PRODUK BOTOL
            </h2>
            <div class="space-y-3 overflow-y-auto pr-2">
                <?php foreach ($produk as $p): ?>
                <div class="bg-gray-50 p-4 rounded-lg border-l-8 border-blue-500">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <p class="font-bold text-lg"><?= $p['nama_produk'] ?></p>
                            <p class="text-sm text-gray-600">Kode: <?= $p['kode_produk'] ?></p>
                            <div class="grid grid-cols-3 gap-2 mt-2 text-sm">
                                <div>Beli: <?= rupiah($p['harga_beli']) ?></div>
                                <div>Jual: <?= rupiah($p['harga_jual']) ?></div>
                                <div>Dus: <?= rupiah($p['harga_dus']) ?></div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2 ml-4">
                            <button onclick="editProduct(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_produk']) ?>', <?= $p['harga_beli'] ?>, <?= $p['harga_jual'] ?>, <?= $p['harga_dus'] ?>)"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-2 rounded text-sm font-semibold whitespace-nowrap">
                                ✏️ Edit
                            </button>
                            <button onclick="deleteProduct(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_produk']) ?>')"
                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm font-semibold whitespace-nowrap">
                                🗑️ Hapus
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
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
                <label class="block text-gray-700 font-bold mb-2">🥤 Nama Produk</label>
                <label for="edit_nama"></label><input type="text" name="nama_produk" id="edit_nama" required class="w-full border-2 border-gray-300 rounded-lg p-3 text-lg">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 font-bold mb-2">💰 Harga Beli</label>
                    <label for="edit_beli"></label><input type="number" name="harga_beli" id="edit_beli" required class="w-full border-2 border-gray-300 rounded-lg p-3 text-lg">
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-2">💵 Harga Jual</label>
                    <label for="edit_jual"></label><input type="number" name="harga_jual" id="edit_jual" required class="w-full border-2 border-gray-300 rounded-lg p-3 text-lg">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">📦 Harga Dus</label>
                <label for="edit_dus"></label><input type="number" name="harga_dus" id="edit_dus" required class="w-full border-2 border-gray-300 rounded-lg p-3 text-lg">
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
    const confirmed = await confirmDelete(`Hapus produk "${nama}"?\n\nData produk dan stok di semua cabang akan dihapus!`);
    if (confirmed) {
        window.location.href = '?delete=' + id;
    }
}

function editProduct(id, nama, hargaBeli, hargaJual, hargaDus) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_beli').value = hargaBeli;
    document.getElementById('edit_jual').value = hargaJual;
    document.getElementById('edit_dus').value = hargaDus;
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