<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!is_login()) redirect('../../login.php');

$transaction_label = 'TRANSAKSI PEMBELI';
$title = is_admin() ? $transaction_label : $transaction_label . ' - ' . $_SESSION['user']['nama'];

include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';

$cabang_id = is_admin() ? ($_GET['cabang'] ?? 1) : $_SESSION['user']['cabang_id'];
$nama_cabang = query("SELECT nama_cabang FROM cabang WHERE id = $cabang_id")[0]['nama_cabang'];
$produk = get_produk();

include '../../includes/modal_confirm.php';

// Proses transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_transaksi'])) {
    $cart_items = json_decode($_POST['cart_data'], true);
    if (!empty($cart_items)) {
        $transaction_items = [];
        $total_transaction = 0;
        $errors = [];

        foreach ($cart_items as $item) {
            $produk_id = $item['produk_id'];
            $jumlah = intval($item['jumlah']);
            $satuan = $item['satuan'];

            $data_produk = query("SELECT * FROM produk WHERE id = $produk_id")[0];
            
            if ($satuan === 'dus') {
                $harga_satuan = (float)$data_produk['harga_dus'];
                $jumlah_botol = $jumlah * 12;
            } else {
                $harga_satuan = (float)$data_produk['harga_jual'];
                $jumlah_botol = $jumlah;
            }

            $subtotal = $harga_satuan * $jumlah;

            $stok_cabang = get_stok_cabang($produk_id, $cabang_id);
            if ($stok_cabang < $jumlah_botol) {
                $errors[] = $data_produk['nama_produk'] . " - Stok tidak mencukupi! Sisa: $stok_cabang botol";
                continue;
            }

            $transaction_items[] = [
                'produk_id' => $produk_id,
                'jumlah' => $jumlah,
                'satuan' => $satuan,
                'harga_satuan' => $harga_satuan,
                'harga_tawar' => null,
                'selisih' => null,
                'subtotal' => $subtotal
            ];
            $total_transaction += $subtotal;
        }

        if (empty($errors) && !empty($transaction_items)) {
            $nama_kasir = $_SESSION['user']['nama'];
            $invoice = generate_invoice();
            $header_data = [
                'no_invoice' => $invoice,
                'cabang_id' => $cabang_id,
                'session_kasir_id' => is_admin() ? null : $_SESSION['user']['id'],
                'nama_kasir' => $nama_kasir,
                'tipe' => 'pembeli',
                'total_harga' => $total_transaction
            ];

            $result = save_transaction($header_data, $transaction_items);
            if ($result['success']) $success = $result['message'];
            else $error = $result['message'];
        } elseif (!empty($errors)) $error = implode('<br>', $errors);
    }
}

// Rekap hari ini
$rekap = query("
    SELECT 
        COUNT(*) as total_transaksi,
        SUM(total_harga) as total_penjualan
    FROM transaksi_header 
    WHERE cabang_id = $cabang_id AND tipe = 'pembeli' AND DATE(created_at) = CURDATE()
")[0];
?>

<style>
    .product-card { transition: all 0.2s; cursor: pointer; }
    .product-card:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.15); }
    .product-card:active { transform: scale(0.98); }
    .cart-sidebar { position: sticky; top: 20px; max-height: calc(100vh - 40px); overflow-y: auto; }
    .qty-btn { transition: all 0.15s; }
    .qty-btn:active { transform: scale(0.9); }
</style>

<div class="p-4 lg:p-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h1 class="judul text-2xl lg:text-3xl font-bold text-gray-800 flex items-center">
            <span class="mr-3">🛒</span> TRANSAKSI PEMBELI
        </h1>
        <p class="text-sm text-gray-600 mt-1">👤 <?= $_SESSION['user']['nama'] ?> | 📍 <?= $nama_cabang ?></p>
        <?php if (is_admin()): ?>
        <div class="mt-3">
            <select onchange="window.location.href='?cabang='+this.value" class="border rounded-lg p-2 text-sm">
                <?php foreach (get_cabang() as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id'] == $cabang_id ? 'selected' : '' ?>>📍 <?= $c['nama_cabang'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pesan sukses/error -->
    <?php if (isset($success)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">❌ <?= $error ?></div>
    <?php endif; ?>

    <!-- Konten Transaksi -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">🥤 Pilih Produk</h2>
                    <div class="flex bg-gray-100 rounded-lg p-1">
                        <button onclick="setGlobalUnit('botol')" id="unit-botol" class="px-3 py-1 rounded text-xs font-semibold bg-white text-blue-600">Botol</button>
                        <button onclick="setGlobalUnit('dus')" id="unit-dus" class="px-3 py-1 rounded text-xs font-semibold text-gray-500">Dus</button>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                    <?php foreach ($produk as $p): ?>
                        <?php
                            $stok = get_stok_cabang($p['id'], $cabang_id);
                            $is_empty = $stok <= 0;
                        ?>
                        <div class="product-card bg-white border-2 border-gray-200 rounded-lg p-3 <?= $is_empty ? 'opacity-40 grayscale pointer-events-none' : '' ?>"
                             onclick="addToCart(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_produk']) ?>', <?= $p['harga_jual'] ?>, <?= $p['harga_dus'] ?>, <?= $stok ?>)">
                            <div class="bg-linear-to-br from-blue-50 to-blue-100 rounded-lg h-20 flex items-center justify-center mb-2">
                                <span class="text-3xl">🥤</span>
                            </div>
                            <h3 class="font-bold text-sm mb-1 truncate"><?= $p['nama_produk'] ?></h3>
                            <p class="text-xs text-gray-600">Stok: <?= $stok ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="cart-sidebar bg-white rounded-lg shadow p-4">
                <h2 class="text-xl font-bold mb-4 text-blue-700">🛒 Keranjang</h2>
                <div id="cart-items" class="space-y-2 mb-4 max-h-96 overflow-y-auto">
                    <p class="text-gray-400 text-center py-8">Keranjang kosong</p>
                </div>
                <div class="border-t pt-4">
                    <div class="flex justify-between text-lg font-bold mb-1">
                        <span>Total Item</span>
                        <span id="cart-count" class="text-blue-600">0</span>
                    </div>
                    <div class="flex justify-between text-sm font-semibold mb-3 text-indigo-700">
                        <span>Total Belanja</span>
                        <span id="grand-total">Rp 0</span>
                    </div>
                    <form method="POST" id="checkout-form">
                        <input type="hidden" name="simpan_transaksi" value="1">
                        <input type="hidden" name="cart_data" id="form-cart-data">
                        <div class="mb-3">
                            <label class="block text-sm font-medium mb-2">Keterangan</label>
                            <input type="text" name="keterangan" class="w-full border rounded-lg p-2 text-sm" placeholder="Opsional...">
                        </div>
                        <button type="submit" id="btn-bayar" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg disabled:opacity-50" disabled>✅ PROSES</button>
                    </form>
                    <button onclick="confirmClearCart()" class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-lg text-sm mt-2">🗑️ Kosongkan</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];
let globalUnit = 'botol';

function setGlobalUnit(unit) {
    globalUnit = unit;
    const btnBotol = document.getElementById('unit-botol');
    const btnDus = document.getElementById('unit-dus');
    
    if(unit === 'botol') {
        btnBotol.className = 'px-3 py-1 rounded text-xs font-semibold bg-white text-blue-600';
        btnDus.className = 'px-3 py-1 rounded text-xs font-semibold text-gray-500';
    } else {
        btnBotol.className = 'px-3 py-1 rounded text-xs font-semibold text-gray-500';
        btnDus.className = 'px-3 py-1 rounded text-xs font-semibold bg-white text-blue-600';
    }
}

function addToCart(id, nama, hargaJual, hargaDus, stok) {
    const existing = cart.find(i => i.produk_id === id && i.satuan === globalUnit);
    if(existing) {
        existing.jumlah++;
    } else {
        cart.push({
            produk_id: id, nama, harga_jual: hargaJual, harga_dus: hargaDus,
            jumlah: 1, satuan: globalUnit, stok
        });
    }
    renderCart();
}

function updateQty(index, delta) {
    cart[index].jumlah += delta;
    if(cart[index].jumlah <= 0) cart.splice(index, 1);
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cart-items');
    const totalEl = document.getElementById('grand-total');
    const countEl = document.getElementById('cart-count');
    const btn = document.getElementById('btn-bayar');

    if(cart.length === 0) {
        container.innerHTML = '<p class="text-gray-400 text-center py-8">Keranjang kosong</p>';
        totalEl.innerText = 'Rp 0';
        countEl.innerText = '0';
        btn.disabled = true;
        return;
    }

    let total = 0;
    let html = '';
    cart.forEach((item, idx) => {
        const harga = item.satuan === 'dus' ? item.harga_dus : item.harga_jual;
        const sub = harga * item.jumlah;
        const totalBotol = item.satuan === 'dus' ? item.jumlah * 12 : item.jumlah;
        total += sub;

        html += `<div class="border rounded-lg p-2 bg-gray-50">
            <div class="flex justify-between items-start mb-1">
                <div><h4 class="font-semibold text-sm">${item.nama}</h4><p class="text-xs text-gray-600">Stok: ${item.stok} btl</p></div>
                <button onclick="cart.splice(${idx},1); renderCart();" class="text-red-500 text-sm">✕</button>
            </div>
            <div class="flex items-center gap-1 mb-1">
                <span class="text-xs text-gray-500">${item.satuan === 'dus' ? 'Per Dus' : 'Per Botol'}:</span>
                <span class="text-xs font-bold text-indigo-700">${formatRp(harga)}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <button onclick="updateQty(${idx}, -1)" class="qty-btn bg-gray-300 w-7 h-7 rounded">−</button>
                    <span class="font-bold w-12 text-center text-sm">${numFormat(item.jumlah)}</span>
                    <button onclick="updateQty(${idx}, 1)" class="qty-btn bg-blue-600 text-white w-7 h-7 rounded">+</button>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-gray-500">${numFormat(totalBotol)} botol</p>
                    <p class="text-xs font-bold text-blue-700">${formatRp(sub)}</p>
                </div>
            </div>
        </div>`;
    });

    container.innerHTML = html;
    totalEl.innerText = formatRp(total);
    countEl.innerText = cart.length;
    btn.disabled = false;
}

function formatRp(n) {
    return 'Rp ' + parseFloat(n || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function numFormat(n) {
    return parseInt(n || 0).toLocaleString('id-ID');
}

async function confirmClearCart() {
    const ok = await confirmClear('Kosongkan semua item di keranjang?');
    if (ok) { cart = []; renderCart(); }
}

document.getElementById('checkout-form').addEventListener('submit', function(e) {
    if(!cart.length) { e.preventDefault(); return; }
    document.getElementById('form-cart-data').value = JSON.stringify(cart);
});

renderCart();
</script>

<?php include '../../includes/layout_footer.php'; ?>
