<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!is_login()) redirect('../../login.php');

// Handler dismiss notifikasi selisih
if (isset($_GET['dismiss']) && isset($_GET['id'])) {
    $so_id = intval($_GET['id']);
    execute("UPDATE stock_opname SET is_hidden = 1 WHERE id = $so_id");
    header("Location: stok_keluar.php");
    exit;
}
if (isset($_GET['dismiss_so']) && isset($_GET['id'])) {
    $so_id = intval($_GET['id']);
    execute("UPDATE stock_opname SET is_hidden = 1 WHERE id = $so_id");
    header("Location: stok_keluar.php");
    exit;
}

// Proses stok keluar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_stok_keluar'])) {
    $cart_items = json_decode($_POST['cart_data'], true);
    $kondisi = $_POST['kondisi'];
    $cabang_tujuan = ($kondisi == 'transfer' && isset($_POST['cabang_tujuan'])) ? $_POST['cabang_tujuan'] : 'NULL';
    $keterangan = escape_string($_POST['keterangan']);

    if (!empty($cart_items)) {
        $batch_id = 'SK-' . date('Ymd') . '-' . rand(1000, 9999);
        $success_count = 0;
        $errors = [];

        foreach ($cart_items as $item) {
            $produk_id = $item['produk_id'];
            $jumlah = intval($item['jumlah']);
            $stok_gudang = get_stok_gudang($produk_id);

            if ($stok_gudang >= $jumlah) {
                execute("UPDATE produk SET stok_gudang = stok_gudang - $jumlah WHERE id = $produk_id");

                if ($kondisi == 'transfer' && $cabang_tujuan != 'NULL') {
                    $check = query("SELECT * FROM stok_cabang WHERE produk_id = $produk_id AND cabang_id = $cabang_tujuan");
                    if (count($check) > 0) {
                        execute("UPDATE stok_cabang SET stok = stok + $jumlah WHERE produk_id = $produk_id AND cabang_id = $cabang_tujuan");
                    } else {
                        execute("INSERT INTO stok_cabang (produk_id, cabang_id, stok) VALUES ($produk_id, $cabang_tujuan, $jumlah)");
                    }
                }

                $sql = "INSERT INTO stok_keluar (produk_id, jumlah, kondisi, cabang_tujuan, keterangan, batch_id)
                        VALUES ($produk_id, $jumlah, '$kondisi', " . ($cabang_tujuan == 'NULL' ? 'NULL' : $cabang_tujuan) . ", '$keterangan', '$batch_id')";
                execute($sql);
                $success_count++;
            } else {
                $errors[] = $item['nama'] . " - Stok tidak cukup (tersedia: $stok_gudang)";
            }
        }

        if ($success_count > 0) {
            $success = "✅ Stok keluar berhasil! $success_count produk diproses. Batch: $batch_id";
        }
        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        }
    }
}

$title = 'Stok Keluar - Gudang';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';

$produk = get_produk();
$cek_hilang = cek_selisih_stok();
$cabang = get_cabang();
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
        .close-notif { position: absolute; top: 10px; right: 15px; background: rgba(255,255,255,0.2); border: none; color: white; font-size: 24px; font-weight: bold; cursor: pointer; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 20; text-decoration: none; }
        .close-notif:hover { background: rgba(255,255,255,0.4); transform: scale(1.1); }
    </style>

    <div class="p-4 lg:p-6">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h1 class="judul text-2xl lg:text-3xl font-bold text-gray-800 flex items-center">
                <span class="mr-3">📤</span> STOK KELUAR
            </h1>
            <p class="text-sm text-gray-600 mt-1">👤 <?= $_SESSION['user']['nama'] ?></p>
        </div>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded"><?= $success ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">❌ <?= $error ?></div>
        <?php endif; ?>

        <!-- Konten Stok Keluar -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-4">
                    <h2 class="text-xl font-bold mb-4">🥤 Pilih Produk</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                        <?php foreach ($produk as $p): ?>
                            <?php $disabled = $p['stok_gudang'] <= 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>
                            <div class="product-card bg-white border-2 border-gray-200 rounded-lg p-3 <?= $disabled ?>"
                                 onclick="<?= $p['stok_gudang'] > 0 ? 'addToCartKeluar('.$p['id'].', \''.htmlspecialchars($p['nama_produk']).'\', '.$p['stok_gudang'].')' : '' ?>">
                                <div class="bg-linear-to-br from-yellow-50 to-yellow-100 rounded-lg h-20 flex items-center justify-center mb-2">
                                    <span class="text-3xl">🥤</span>
                                </div>
                                <h3 class="font-bold text-sm mb-1 truncate"><?= $p['nama_produk'] ?></h3>
                                <p class="text-xs <?= $p['stok_gudang'] > 0 ? 'text-green-600' : 'text-red-600' ?>">Stok: <?= $p['stok_gudang'] ?> btl</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="cart-sidebar bg-white rounded-lg shadow p-4">
                    <h2 class="text-xl font-bold mb-4 text-yellow-700">📤 Keranjang</h2>

                    <!-- Kondisi Toggle -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Tipe:</label>
                        <div class="flex gap-2">
                            <button type="button" onclick="setKondisi('rusak')" id="btn-rusak" class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold bg-red-600 text-white">🔴 Rusak</button>
                            <button type="button" onclick="setKondisi('transfer')" id="btn-transfer" class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold bg-gray-200 text-gray-700">🔄 Transfer</button>
                        </div>
                    </div>

                    <!-- Cabang Tujuan -->
                    <div id="div-cabang-tujuan" class="mb-4 hidden">
                        <label class="block text-sm font-medium mb-2">Tujuan Cabang:</label>
                        <select id="select-cabang" class="w-full border rounded-lg p-2 text-sm">
                            <?php foreach ($cabang as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="cart-keluar-items" class="space-y-2 mb-4 max-h-64 overflow-y-auto">
                        <p class="text-gray-400 text-center py-8">Keranjang kosong</p>
                    </div>

                    <div class="border-t pt-4">
                        <div class="flex justify-between text-lg font-bold mb-3">
                            <span>Total Item</span>
                            <span id="cart-keluar-count" class="text-yellow-600">0</span>
                        </div>
                        <form method="POST" id="form-stok-keluar">
                            <input type="hidden" name="batch_stok_keluar" value="1">
                            <input type="hidden" name="cart_data" id="cart-keluar-data">
                            <input type="hidden" name="kondisi" id="form-kondisi" value="rusak">
                            <input type="hidden" name="cabang_tujuan" id="form-cabang-tujuan">

                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-2">Keterangan</label>
                                <textarea name="keterangan" rows="2" class="w-full border rounded-lg p-2 text-sm" placeholder="..."></textarea>
                            </div>
                            <button type="submit" id="btn-submit-keluar" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-3 rounded-lg disabled:opacity-50" disabled>✅ PROSES</button>
                        </form>
                        <button onclick="clearCartKeluar()" class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-lg text-sm mt-2">🗑️ Kosongkan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cartKeluar = [];
        let kondisi = 'rusak';

        function setKondisi(type) {
            kondisi = type;
            document.getElementById('form-kondisi').value = type;
            if (type === 'rusak') {
                document.getElementById('btn-rusak').className = 'flex-1 py-2 px-3 rounded-lg text-sm font-semibold bg-red-600 text-white';
                document.getElementById('btn-transfer').className = 'flex-1 py-2 px-3 rounded-lg text-sm font-semibold bg-gray-200 text-gray-700';
                document.getElementById('div-cabang-tujuan').classList.add('hidden');
            } else {
                document.getElementById('btn-rusak').className = 'flex-1 py-2 px-3 rounded-lg text-sm font-semibold bg-gray-200 text-gray-700';
                document.getElementById('btn-transfer').className = 'flex-1 py-2 px-3 rounded-lg text-sm font-semibold bg-blue-600 text-white';
                document.getElementById('div-cabang-tujuan').classList.remove('hidden');
            }
        }

        function addToCartKeluar(id, nama, stokGudang) {
            let item = cartKeluar.find(i => i.produk_id === id);
            if (item) {
                if (item.jumlah < stokGudang) item.jumlah++;
                else { alert('Melebihi stok!'); return; }
            } else {
                cartKeluar.push({ produk_id: id, nama, jumlah: 1, stok_gudang: stokGudang });
            }
            renderCartKeluar();
        }

        function updateQtyKeluar(index, delta) {
            let newQty = cartKeluar[index].jumlah + delta;
            if (newQty <= 0) cartKeluar.splice(index, 1);
            else if (newQty > cartKeluar[index].stok_gudang) alert('Melebihi stok!');
            else cartKeluar[index].jumlah = newQty;
            renderCartKeluar();
        }

        function renderCartKeluar() {
            let container = document.getElementById('cart-keluar-items');
            let countSpan = document.getElementById('cart-keluar-count');
            let btn = document.getElementById('btn-submit-keluar');
            if (!cartKeluar.length) {
                container.innerHTML = '<p class="text-gray-400 text-center py-8">Keranjang kosong</p>';
                countSpan.innerText = '0';
                btn.disabled = true;
                return;
            }
            let html = '';
            cartKeluar.forEach((item, idx) => {
                html += `<div class="border rounded-lg p-2 bg-gray-50">
            <div class="flex justify-between items-start mb-2">
                <div><h4 class="font-semibold text-sm">${item.nama}</h4><p class="text-xs text-gray-600">Max: ${item.stok_gudang}</p></div>
                <button onclick="cartKeluar.splice(${idx},1); renderCartKeluar();" class="text-red-500 text-sm">✕</button>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="updateQtyKeluar(${idx}, -1)" class="qty-btn bg-gray-300 w-7 h-7 rounded">−</button>
                <span class="font-bold w-12 text-center">${item.jumlah}</span>
                <button onclick="updateQtyKeluar(${idx}, 1)" class="qty-btn bg-yellow-600 text-white w-7 h-7 rounded">+</button>
                <span class="text-xs ml-auto">botol</span>
            </div>
        </div>`;
            });
            container.innerHTML = html;
            countSpan.innerText = cartKeluar.length;
            btn.disabled = false;
        }

        function clearCartKeluar() { if (confirm('Kosongkan keranjang?')) { cartKeluar = []; renderCartKeluar(); } }

        document.getElementById('form-stok-keluar').addEventListener('submit', function(e) {
            if (!cartKeluar.length) { e.preventDefault(); alert('Keranjang kosong!'); return; }
            if (kondisi === 'transfer') document.getElementById('form-cabang-tujuan').value = document.getElementById('select-cabang').value;
            document.getElementById('cart-keluar-data').value = JSON.stringify(cartKeluar);
        });

        renderCartKeluar();
    </script>

<?php include '../../includes/layout_footer.php'; ?>