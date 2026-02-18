<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
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

// Proses transaksi (multiple items)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_transaksi'])) {
    $cart_items = json_decode($_POST['cart_data'], true);
    $tipe = $_POST['tipe'];

    if (!empty($cart_items)) {
        $invoice = generate_invoice();
        $success_count = 0;
        $errors = [];

        foreach ($cart_items as $item) {
            $produk_id = $item['produk_id'];
            $jumlah = intval($item['jumlah']);
            $satuan = $item['satuan'];
            $harga_tawar = isset($item['harga_tawar']) ? intval($item['harga_tawar']) : null;

            $data_produk = query("SELECT * FROM produk WHERE id = $produk_id")[0];
            $harga_satuan = 0;
            $selisih = null;

            if ($tipe === 'pembeli') {
                if ($satuan === 'dus') {
                    $harga_satuan = $data_produk['harga_dus'];
                    $jumlah_botol = $jumlah * 12;
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

            // Kurangi stok cabang
            $stok_cabang = get_stok_cabang($produk_id, $cabang_id);
            if ($stok_cabang >= $jumlah_botol) {
                execute("UPDATE stok_cabang SET stok = stok - $jumlah_botol WHERE produk_id = $produk_id AND cabang_id = $cabang_id");

                // Simpan transaksi
                $nama_kasir = $_SESSION['user']['nama'];
                $session_id = is_admin() ? 'NULL' : $_SESSION['user']['id'];

                $sql = "INSERT INTO transaksi (no_invoice, produk_id, cabang_id, session_kasir_id, nama_kasir, tipe, jumlah, satuan, harga_satuan, harga_tawar, selisih, total_harga)
                        VALUES ('$invoice', $produk_id, $cabang_id, $session_id, '$nama_kasir', '$tipe', $jumlah, '$satuan', $harga_satuan, " . ($harga_tawar ?? 'NULL') . ", " . ($selisih ?? 'NULL') . ", $total_harga)";

                if (execute($sql)) {
                    $success_count++;
                }
            } else {
                $errors[] = $data_produk['nama_produk'] . " - Stok tidak mencukupi! Sisa: $stok_cabang botol";
            }
        }

        if ($success_count > 0) {
            $success = "Transaksi berhasil! $success_count item diproses. Invoice: $invoice";
        }
        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        }
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
?>

<style>
    .product-card {
        transition: all 0.2s;
        cursor: pointer;
    }
    .product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }
    .product-card:active {
        transform: scale(0.98);
    }
    .cart-sidebar {
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 40px);
        overflow-y: auto;
    }
    .qty-btn {
        transition: all 0.15s;
    }
    .qty-btn:active {
        transform: scale(0.9);
    }
</style>

<div class="p-4 lg:p-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-3">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">KASIR POS</h1>
            <p class="text-sm text-gray-600 mt-1">
                📍 <?= $nama_cabang ?> | 👤 <?= $_SESSION['user']['nama'] ?>
            </p>
        </div>
        <?php if (is_admin()): ?>
            <label>
                <select onchange="window.location.href='?cabang='+this.value" class="border rounded-lg p-2">
                    <?php foreach (get_cabang() as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $cabang_id ? 'selected' : '' ?>>
                        <?= $c['nama_cabang'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
    </div>

    <!-- Rekap Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="bg-linear-to-r from-blue-500 to-blue-600 text-white rounded-lg shadow p-4">
            <p class="text-xs opacity-90">Penjualan Hari Ini</p>
            <p class="text-2xl font-bold"><?= rupiah($rekap['total_penjualan'] ?? 0) ?></p>
            <p class="text-xs mt-1"><?= ($rekap['total_transaksi'] ?? 0) ?> transaksi</p>
        </div>
        <div class="bg-linear-to-r from-green-500 to-green-600 text-white rounded-lg shadow p-4">
            <p class="text-xs opacity-90">Pembelian dari Penjual</p>
            <p class="text-2xl font-bold"><?= rupiah($rekap['total_pembelian'] ?? 0) ?></p>
        </div>
        <div class="bg-linear-to-r from-purple-500 to-purple-600 text-white rounded-lg shadow p-4">
            <p class="text-xs opacity-90">Item di Keranjang</p>
            <p class="text-2xl font-bold" id="cart-count">0</p>
        </div>
    </div>

    <?php if (isset($success)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
        ✅ <?= $success ?>
    </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
        ❌ <?= $error ?>
    </div>
    <?php endif; ?>

    <!-- Main POS Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Left: Product Grid -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-4">
                <h2 class="text-xl font-bold mb-4">Pilih Produk</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                    <?php foreach ($produk as $p): ?>
                    <?php
                        $stok = get_stok_cabang($p['id'], $cabang_id);
                        $disabled = $stok <= 0 ? 'opacity-50 cursor-not-allowed' : '';
                    ?>
                    <div class="product-card bg-white border-2 border-gray-200 rounded-lg p-3 <?= $disabled ?>"
                         onclick="<?= $stok > 0 ? 'addToCart('.$p['id'].', \''.htmlspecialchars($p['nama_produk']).'\', '.$p['harga_jual'].', '.$p['harga_dus'].', '.$stok.')' : '' ?>"
                         data-id="<?= $p['id'] ?>">
                        <div class="bg-linear-to-br from-blue-50 to-blue-100 rounded-lg h-20 flex items-center justify-center mb-2">
                            <span class="text-3xl">🥤</span>
                        </div>
                        <h3 class="font-bold text-sm mb-1 truncate"><?= $p['nama_produk'] ?></h3>
                        <p class="text-blue-600 font-bold text-sm">
                            <?= rupiah($p['harga_jual']) ?>
                        </p>
                        <p class="text-xs text-gray-500">
                            Dus: <?= rupiah($p['harga_dus']) ?>
                        </p>
                        <p class="text-xs mt-1 <?= $stok > 0 ? 'text-green-600' : 'text-red-600' ?>">
                            Stok: <?= $stok ?> btl
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right: Cart Sidebar -->
        <div class="lg:col-span-1">
            <div class="cart-sidebar bg-white rounded-lg shadow p-4">
                <h2 class="text-xl font-bold mb-4">Keranjang</h2>

                <!-- Transaction Type Toggle -->
                <div class="mb-4">
                    <div class="flex gap-2">
                        <button type="button" onclick="setTransactionType('pembeli')"
                                id="btn-pembeli"
                                class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold bg-blue-600 text-white">
                            Pembeli
                        </button>
                        <button type="button" onclick="setTransactionType('penjual')"
                                id="btn-penjual"
                                class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold bg-gray-200 text-gray-700">
                            Penjual
                        </button>
                    </div>
                </div>

                <!-- Cart Items -->
                <div id="cart-items" class="space-y-2 mb-4 max-h-96 overflow-y-auto">
                    <p class="text-gray-400 text-center py-8 text-sm">Keranjang kosong</p>
                </div>

                <!-- Total -->
                <div class="border-t pt-4 space-y-2">
                    <div class="flex justify-between text-lg font-bold">
                        <span>TOTAL</span>
                        <span id="grand-total" class="text-blue-600">Rp 0</span>
                    </div>

                    <!-- Bayar Button -->
                    <form method="POST" id="checkout-form">
                        <input type="hidden" name="simpan_transaksi" value="1">
                        <input type="hidden" name="tipe" id="form-tipe" value="pembeli">
                        <input type="hidden" name="cart_data" id="form-cart-data">

                        <button type="submit" id="btn-bayar"
                                class="w-full btn-primary text-white font-bold py-3 rounded-lg text-lg disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled>
                            💵 BAYAR
                        </button>
                    </form>

                    <button type="button" onclick="clearCart()"
                            class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-lg text-sm">
                        🗑️ Kosongkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];
let transactionType = 'pembeli';

function setTransactionType(type) {
    transactionType = type;
    document.getElementById('form-tipe').value = type;

    // Update button styles
    if (type === 'pembeli') {
        document.getElementById('btn-pembeli').className = 'flex-1 py-2 px-3 rounded-lg text-sm font-semibold bg-blue-600 text-white';
        document.getElementById('btn-penjual').className = 'flex-1 py-2 px-3 rounded-lg text-sm font-semibold bg-gray-200 text-gray-700';
    } else {
        document.getElementById('btn-pembeli').className = 'flex-1 py-2 px-3 rounded-lg text-sm font-semibold bg-gray-200 text-gray-700';
        document.getElementById('btn-penjual').className = 'flex-1 py-2 px-3 rounded-lg text-sm font-semibold bg-green-600 text-white';
    }

    renderCart();
}

function addToCart(id, nama, hargaJual, hargaDus, stok) {
    // Check if product already in cart
    const existingItem = cart.find(item => item.produk_id === id && item.satuan === 'botol');

    if (transactionType === 'penjual') {
        // For penjual, ask for negotiated price
        const hargaTawar = prompt('Masukkan harga tawar per botol untuk ' + nama + ':', hargaJual);
        if (!hargaTawar || hargaTawar <= 0) return;

        if (existingItem) {
            existingItem.jumlah++;
            existingItem.harga_tawar = parseInt(hargaTawar);
        } else {
            cart.push({
                produk_id: id,
                nama: nama,
                harga_jual: hargaJual,
                harga_dus: hargaDus,
                harga_tawar: parseInt(hargaTawar),
                jumlah: 1,
                satuan: 'botol',
                stok: stok
            });
        }
    } else {
        // For pembeli, just add
        if (existingItem) {
            existingItem.jumlah++;
        } else {
            cart.push({
                produk_id: id,
                nama: nama,
                harga_jual: hargaJual,
                harga_dus: hargaDus,
                jumlah: 1,
                satuan: 'botol',
                stok: stok
            });
        }
    }

    renderCart();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function updateQuantity(index, change) {
    cart[index].jumlah += change;

    if (cart[index].jumlah <= 0) {
        removeFromCart(index);
    } else {
        renderCart();
    }
}

function changeSatuan(index, satuan) {
    cart[index].satuan = satuan;
    renderCart();
}

function formatRupiah(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

function renderCart() {
    const cartItemsDiv = document.getElementById('cart-items');
    const grandTotalSpan = document.getElementById('grand-total');
    const cartCountSpan = document.getElementById('cart-count');
    const btnBayar = document.getElementById('btn-bayar');

    if (cart.length === 0) {
        cartItemsDiv.innerHTML = '<p class="text-gray-400 text-center py-8 text-sm">Keranjang kosong</p>';
        grandTotalSpan.textContent = 'Rp 0';
        cartCountSpan.textContent = '0';
        btnBayar.disabled = true;
        return;
    }

    let html = '';
    let grandTotal = 0;

    cart.forEach((item, index) => {
        let hargaSatuan;
        let jumlahBotol = item.jumlah;

        if (transactionType === 'pembeli') {
            if (item.satuan === 'dus') {
                hargaSatuan = item.harga_dus;
                jumlahBotol = item.jumlah * 12;
            } else {
                hargaSatuan = item.harga_jual;
            }
        } else {
            hargaSatuan = item.harga_tawar || item.harga_jual;
        }

        const subtotal = hargaSatuan * item.jumlah;
        grandTotal += subtotal;

        html += `
            <div class="border rounded-lg p-2 bg-gray-50">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1">
                        <h4 class="font-semibold text-sm">${item.nama}</h4>
                        <p class="text-xs text-gray-600">${formatRupiah(hargaSatuan)} / ${item.satuan}</p>
                        ${transactionType === 'penjual' && item.harga_tawar ?
                            `<p class="text-xs text-green-600">Harga tawar: ${formatRupiah(item.harga_tawar)}</p>` : ''}
                    </div>
                    <button onclick="removeFromCart(${index})" class="text-red-500 hover:text-red-700 text-sm">
                        ✕
                    </button>
                </div>

                ${transactionType === 'pembeli' ? `
                <div class="flex gap-1 mb-2">
                    <button onclick="changeSatuan(${index}, 'botol')"
                            class="flex-1 py-1 px-2 text-xs rounded ${item.satuan === 'botol' ? 'bg-blue-600 text-white' : 'bg-gray-200'}">
                        Botol
                    </button>
                    <button onclick="changeSatuan(${index}, 'dus')"
                            class="flex-1 py-1 px-2 text-xs rounded ${item.satuan === 'dus' ? 'bg-blue-600 text-white' : 'bg-gray-200'}">
                        Dus
                    </button>
                </div>
                ` : ''}

                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <button onclick="updateQuantity(${index}, -1)"
                                class="qty-btn bg-gray-300 hover:bg-gray-400 w-7 h-7 rounded flex items-center justify-center font-bold">
                            −
                        </button>
                        <span class="font-bold w-8 text-center">${item.jumlah}</span>
                        <button onclick="updateQuantity(${index}, 1)"
                                class="qty-btn bg-blue-600 hover:bg-blue-700 text-white w-7 h-7 rounded flex items-center justify-center font-bold">
                            +
                        </button>
                    </div>
                    <span class="font-bold text-blue-600">${formatRupiah(subtotal)}</span>
                </div>
            </div>
        `;
    });

    cartItemsDiv.innerHTML = html;
    grandTotalSpan.textContent = formatRupiah(grandTotal);
    cartCountSpan.textContent = cart.length;
    btnBayar.disabled = false;
}

function clearCart() {
    if (confirm('Kosongkan keranjang?')) {
        cart = [];
        renderCart();
    }
}

// Submit form
document.getElementById('checkout-form').addEventListener('submit', function(e) {
    if (cart.length === 0) {
        e.preventDefault();
        alert('Keranjang masih kosong!');
        return;
    }

    document.getElementById('form-cart-data').value = JSON.stringify(cart);
});

// Initial render
renderCart();
</script>

<?php include '../../includes/layout_footer.php'; ?>
