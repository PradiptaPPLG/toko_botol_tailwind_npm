<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!is_login()) redirect('../../login.php');

// Handler dismiss notifikasi selisih
if (isset($_GET['dismiss']) && isset($_GET['id'])) {
    $so_id = intval($_GET['id']);
    execute("UPDATE stock_opname SET is_hidden = 1 WHERE id = $so_id");
    header("Location: stok_masuk.php");
    exit;
}
if (isset($_GET['dismiss_so']) && isset($_GET['id'])) {
    $so_id = intval($_GET['id']);
    execute("UPDATE stock_opname SET is_hidden = 1 WHERE id = $so_id");
    header("Location: stok_masuk.php");
    exit;
}

// Proses stok masuk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_stok_masuk'])) {
    $cart_items = json_decode($_POST['cart_data'], true);
    $keterangan = escape_string($_POST['keterangan']);

    if (!empty($cart_items)) {
        $batch_id = 'SM-' . date('Ymd') . '-' . rand(1000, 9999);
        $success_count = 0;

        foreach ($cart_items as $item) {
            $produk_id = $item['produk_id'];
            $jumlah = intval($item['jumlah']);

            execute("UPDATE produk SET stok_gudang = stok_gudang + $jumlah WHERE id = $produk_id");
            execute("INSERT INTO stok_masuk (produk_id, jumlah, keterangan, batch_id) VALUES ($produk_id, $jumlah, '$keterangan', '$batch_id')");
            $success_count++;
        }
        $success = "✅ Stok masuk berhasil! $success_count produk ditambahkan. Batch: $batch_id";
    }
}

$title = 'Stok Masuk - Gudang';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';

$produk = get_produk();
$cek_hilang = cek_selisih_stok();
include '../../includes/modal_confirm.php';
?>

    <style>
        .product-card { transition: all 0.2s; cursor: pointer; }
        .product-card:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.15); }
        .product-card:active { transform: scale(0.98); }
        .cart-sidebar { position: sticky; top: 20px; max-height: calc(100vh - 40px); overflow-y: auto; }
        .qty-btn { transition: all 0.15s; }
        .qty-btn:active { transform: scale(0.9); }
        .notif-item { position: relative; padding-right: 40px; }
        .close-notif { position: absolute; top: 10px; right: 15px; background: rgba(255,255,255,0.2); border: none; color: white; font-size: 24px; font-weight: bold; cursor: pointer; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s; z-index: 20; text-decoration: none; line-height: 1; }
        .close-notif:hover { background: rgba(255,255,255,0.4); transform: scale(1.1); }
    </style>

    <div class="p-4 lg:p-6">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h1 class="judul text-2xl lg:text-3xl font-bold text-gray-800 flex items-center">
                <span class="mr-3">📥</span> STOK MASUK
            </h1>
            <p class="text-sm text-gray-600 mt-1">👤 <?= $_SESSION['user']['nama'] ?></p>
        </div>

        <!-- Pesan sukses/error -->
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded"><?= $success ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">❌ <?= $error ?></div>
        <?php endif; ?>

        <!-- Konten Stok Masuk -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-4">
                    <h2 class="text-xl font-bold mb-4">🥤 Pilih Produk</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                        <?php foreach ($produk as $p): ?>
                            <div class="product-card bg-white border-2 border-gray-200 rounded-lg p-3"
                                 onclick="addToCartMasuk(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_produk']) ?>', <?= $p['stok_gudang'] ?>)">
                                <div class="bg-linear-to-br from-green-50 to-green-100 rounded-lg h-20 flex items-center justify-center mb-2">
                                    <span class="text-3xl">🥤</span>
                                </div>
                                <h3 class="font-bold text-sm mb-1 truncate"><?= $p['nama_produk'] ?></h3>
                                <p class="text-xs text-gray-600">Stok: <?= $p['stok_gudang'] ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="cart-sidebar bg-white rounded-lg shadow p-4">
                    <h2 class="text-xl font-bold mb-4 text-green-700">📥 Keranjang</h2>
                    <div id="cart-masuk-items" class="space-y-2 mb-4 max-h-96 overflow-y-auto">
                        <p class="text-gray-400 text-center py-8">Keranjang kosong</p>
                    </div>
                    <div class="border-t pt-4">
                        <div class="flex justify-between text-lg font-bold mb-3">
                            <span>Total Item</span>
                            <span id="cart-masuk-count" class="text-green-600">0</span>
                        </div>
                        <form method="POST" id="form-stok-masuk">
                            <input type="hidden" name="batch_stok_masuk" value="1">
                            <input type="hidden" name="cart_data" id="cart-masuk-data">
                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-2">Keterangan</label>
                                <input type="text" name="keterangan" class="w-full border rounded-lg p-2 text-sm" placeholder="Dari supplier...">
                            </div>
                            <button type="submit" id="btn-submit-masuk" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg disabled:opacity-50" disabled>✅ PROSES</button>
                        </form>
                        <button onclick="clearCartMasuk()" class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-lg text-sm mt-2">🗑️ Kosongkan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cartMasuk = [];
        function addToCartMasuk(id, nama, stokGudang) {
            let item = cartMasuk.find(i => i.produk_id === id);
            if (item) item.jumlah++;
            else cartMasuk.push({ produk_id: id, nama, jumlah: 1, stok_gudang: stokGudang });
            renderCartMasuk();
        }
        function updateQtyMasuk(index, delta) {
            cartMasuk[index].jumlah += delta;
            if (cartMasuk[index].jumlah <= 0) cartMasuk.splice(index, 1);
            renderCartMasuk();
        }
        function renderCartMasuk() {
            let container = document.getElementById('cart-masuk-items');
            let countSpan = document.getElementById('cart-masuk-count');
            let btn = document.getElementById('btn-submit-masuk');
            if (!cartMasuk.length) {
                container.innerHTML = '<p class="text-gray-400 text-center py-8">Keranjang kosong</p>';
                countSpan.innerText = '0';
                btn.disabled = true;
                return;
            }
            let html = '';
            cartMasuk.forEach((item, idx) => {
                html += `<div class="border rounded-lg p-2 bg-gray-50">
            <div class="flex justify-between items-start mb-2">
                <div><h4 class="font-semibold text-sm">${item.nama}</h4><p class="text-xs text-gray-600">Stok: ${item.stok_gudang}</p></div>
                <button onclick="cartMasuk.splice(${idx},1); renderCartMasuk();" class="text-red-500 text-sm">✕</button>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="updateQtyMasuk(${idx}, -1)" class="qty-btn bg-gray-300 w-7 h-7 rounded">−</button>
                <span class="font-bold w-12 text-center">${item.jumlah}</span>
                <button onclick="updateQtyMasuk(${idx}, 1)" class="qty-btn bg-green-600 text-white w-7 h-7 rounded">+</button>
                <span class="text-xs ml-auto">botol</span>
            </div>
        </div>`;
            });
            container.innerHTML = html;
            countSpan.innerText = cartMasuk.length;
            btn.disabled = false;
        }
        function clearCartMasuk() { if (confirm('Kosongkan keranjang?')) { cartMasuk = []; renderCartMasuk(); } }
        document.getElementById('form-stok-masuk').addEventListener('submit', function(e) {
            if (!cartMasuk.length) { e.preventDefault(); alert('Keranjang kosong!'); return; }
            document.getElementById('cart-masuk-data').value = JSON.stringify(cartMasuk);
        });
        renderCartMasuk();
    </script>

<?php include '../../includes/layout_footer.php'; ?>