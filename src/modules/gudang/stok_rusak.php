<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!is_login()) redirect('../../login.php');

// Proses stok rusak
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_stok_rusak'])) {
    $cart_items = json_decode($_POST['cart_data'], true);
    $cabang_asal = intval($_POST['cabang_asal']);
    $keterangan = escape_string($_POST['keterangan']);

    if (!empty($cart_items)) {
        $batch_id = 'SR-' . date('Ymd') . '-' . rand(1000, 9999);
        $success_count = 0;
        $errors = [];

        foreach ($cart_items as $item) {
            $produk_id = $item['produk_id'];
            $satuan = $item['satuan'] ?? 'botol';
            $jumlah_input = intval($item['jumlah']);

            // Convert to botol for DB
            $jumlah = ($satuan === 'dus') ? $jumlah_input * 12 : $jumlah_input;

            // Check stok at selected cabang
            $stok_check = query("SELECT stok FROM stok_cabang WHERE produk_id = $produk_id AND cabang_id = $cabang_asal");
            $stok_asal = count($stok_check) > 0 ? $stok_check[0]['stok'] : 0;

            if ($stok_asal >= $jumlah) {
                // Deduct from cabang_asal
                execute("UPDATE stok_cabang SET stok = stok - $jumlah WHERE produk_id = $produk_id AND cabang_id = $cabang_asal");

                // Record in stok_keluar
                $sql = "INSERT INTO stok_keluar (produk_id, jumlah, kondisi, cabang_asal, cabang_tujuan, keterangan, batch_id)
                        VALUES ($produk_id, $jumlah, 'rusak', $cabang_asal, NULL, '$keterangan', '$batch_id')";
                execute($sql);
                $success_count++;
            } else {
                $errors[] = $item['nama'] . " - Stok tidak cukup di cabang terpilih (tersedia: $stok_asal btl)";
            }
        }

        if ($success_count > 0) {
            $success = "✅ Stok rusak berhasil dicatat! $success_count produk diproses. Batch: $batch_id";
        }
        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        }
    }
}

$title = 'Stok Rusak - Gudang';
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';

$produk = get_produk();
$cabang = get_cabang();
?>

    <div class="p-4 lg:p-6">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h1 class="judul text-2xl lg:text-3xl font-bold text-gray-800 flex items-center">
                <span class="mr-3">🔴</span> STOK RUSAK
            </h1>
            <p class="text-sm text-gray-600 mt-1">👤 <?= $_SESSION['user']['nama'] ?></p>
        </div>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded"><?= $success ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">❌ <?= $error ?></div>
        <?php endif; ?>

        <!-- Konten Stok Rusak -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-3">
                            <h2 class="text-xl font-bold">🥤 Pilih Produk</h2>
                            <div class="flex bg-gray-100 rounded-lg p-1">
                                <button onclick="setGlobalUnit('botol')" id="unit-botol" class="px-3 py-1 rounded text-xs font-semibold bg-white text-red-600">Botol</button>
                                <button onclick="setGlobalUnit('dus')" id="unit-dus" class="px-3 py-1 rounded text-xs font-semibold text-gray-500">Dus</button>
                            </div>
                        </div>

                        <!-- Cabang Selection -->
                        <div class="mb-3">
                            <label class="block text-sm font-medium mb-2 text-gray-700">📍 Cabang:</label>
                            <label for="select-cabang-asal"></label><select id="select-cabang-asal" onchange="loadProdukByCabang()" class="w-full border-2 border-red-300 rounded-lg p-2 text-sm font-semibold bg-red-50">
                                <?php foreach ($cabang as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="produk-list" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                        <!-- Products will be loaded by JavaScript -->
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="cart-sidebar bg-white rounded-lg shadow p-4">
                    <h2 class="text-xl font-bold mb-4 text-red-700">🔴 Stok Rusak</h2>

                    <div id="cart-items" class="space-y-2 mb-4 max-h-64 overflow-y-auto">
                        <p class="text-gray-400 text-center py-8">Keranjang kosong</p>
                    </div>

                    <div class="border-t pt-4">
                        <div class="flex justify-between text-lg font-bold mb-3">
                            <span>Total Item</span>
                            <span id="cart-count" class="text-red-600">0</span>
                        </div>
                        <form method="POST" id="form-rusak">
                            <input type="hidden" name="batch_stok_rusak" value="1">
                            <input type="hidden" name="cart_data" id="cart-data">
                            <input type="hidden" name="cabang_asal" id="form-cabang-asal">

                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-2">Keterangan</label>
                                <label>
                                    <textarea name="keterangan" rows="3" class="w-full border rounded-lg p-2 text-sm" placeholder="Alasan produk rusak: pecah, kadaluwarsa, dll..." required></textarea>
                                </label>
                            </div>
                            <button type="submit" id="btn-submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg disabled:opacity-50" disabled>✅ CATAT STOK RUSAK</button>
                        </form>
                        <button onclick="clearCart()" class="w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 rounded-lg text-sm mt-2">🗑️ Kosongkan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        let globalUnit = 'botol';

        // Product data
        const produkData = <?= json_encode($produk) ?>;

        // Stok cabang data
        let stokCabangData = {};

        // Load stok cabang data
        async function loadStokCabang() {
            try {
                const response = await fetch('../../api/get_stok_cabang.php');
                stokCabangData = await response.json();
                loadProdukByCabang();
            } catch (error) {
                console.error('Error loading stok cabang:', error);
            }
        }

        // Load products based on selected cabang_asal
        function loadProdukByCabang() {
            const cabangId = parseInt(document.getElementById('select-cabang-asal').value);
            const produkList = document.getElementById('produk-list');

            let html = '';
            produkData.forEach(p => {
                const stokKey = `${p.id}_${cabangId}`;
                const stok = stokCabangData[stokKey] || 0;
                const disabled = stok <= 0 ? 'opacity-50 cursor-not-allowed' : '';
                const onclick = stok > 0 ? `addToCart(${p.id}, '${p.nama_produk.replace(/'/g, "\\'\'")}', ${stok})` : '';
                const stokColor = stok > 0 ? 'text-green-600' : 'text-red-600';

                html += `
                    <div class="product-card bg-white border-2 border-gray-200 rounded-lg p-3 ${disabled}"
                         onclick="${onclick}">
                        <div class="bg-linear-to-br from-red-50 to-red-100 rounded-lg h-20 flex items-center justify-center mb-2">
                            <span class="text-3xl">🥤</span>
                        </div>
                        <h3 class="font-bold text-sm mb-1 truncate">${p.nama_produk}</h3>
                        <p class="text-xs ${stokColor}">Stok: ${stok.toLocaleString('id-ID')} btl</p>
                    </div>
                `;
            });

            produkList.innerHTML = html || '<p class="col-span-full text-center text-gray-400 py-8">Tidak ada produk</p>';
        }

        function setGlobalUnit(unit) {
            globalUnit = unit;
            const btnBotol = document.getElementById('unit-botol');
            const btnDus = document.getElementById('unit-dus');

            if(unit === 'botol') {
                btnBotol.className = 'px-3 py-1 rounded text-xs font-semibold bg-white text-red-600';
                btnDus.className = 'px-3 py-1 rounded text-xs font-semibold text-gray-500';
            } else {
                btnBotol.className = 'px-3 py-1 rounded text-xs font-semibold text-gray-500';
                btnDus.className = 'px-3 py-1 rounded text-xs font-semibold bg-white text-red-600';
            }
        }

        function addToCart(id, nama, stok) {
            const satuan = globalUnit;
            let item = cart.find(i => i.produk_id === id && i.satuan === satuan);
            if (item) {
                const newJumlah = item.jumlah + 1;
                const newBotol = satuan === 'dus' ? newJumlah * 12 : newJumlah;
                if (newBotol > stok) { alert('Melebihi stok!'); return; }
                item.jumlah = newJumlah;
            } else {
                const initBotol = satuan === 'dus' ? 12 : 1;
                if (initBotol > stok) { alert('Stok tidak cukup!'); return; }
                cart.push({ produk_id: id, nama, jumlah: 1, stok_gudang: stok, satuan: satuan });
            }
            renderCart();
        }

        function updateQty(index, delta) {
            let newQty = cart[index].jumlah + delta;
            if (newQty <= 0) { cart.splice(index, 1); }
            else {
                const item = cart[index];
                const newBotol = item.satuan === 'dus' ? newQty * 12 : newQty;
                if (newBotol > item.stok_gudang) { alert('Melebihi stok!'); return; }
                cart[index].jumlah = newQty;
            }
            renderCart();
        }

        function setQty(index, value) {
            let newQty = parseInt(value) || 0;
            if (newQty <= 0) {
                cart.splice(index, 1);
            } else {
                const item = cart[index];
                const newBotol = item.satuan === 'dus' ? newQty * 12 : newQty;
                if (newBotol > item.stok_gudang) {
                    alert('Melebihi stok!');
                    renderCart();
                    return;
                }
                cart[index].jumlah = newQty;
            }
            renderCart();
        }

        function numFormat(n) {
            return parseInt(n || 0).toLocaleString('id-ID');
        }

        function renderCart() {
            let container = document.getElementById('cart-items');
            let countSpan = document.getElementById('cart-count');
            let btn = document.getElementById('btn-submit');
            if (!cart.length) {
                container.innerHTML = '<p class="text-gray-400 text-center py-8">Keranjang kosong</p>';
                countSpan.innerText = '0';
                btn.disabled = true;
                return;
            }
            let html = '';
            cart.forEach((item, idx) => {
                const isDus = item.satuan === 'dus';
                const totalBotol = isDus ? item.jumlah * 12 : item.jumlah;
                const unitLabel = isDus ? 'dus' : 'botol';
                html += `<div class="border rounded-lg p-2 bg-gray-50">
            <div class="flex justify-between items-start mb-2">
                <div><h4 class="font-semibold text-sm">${item.nama}</h4><p class="text-xs text-gray-600">Max: ${item.stok_gudang} btl</p></div>
                <button onclick="cart.splice(${idx},1); renderCart();" class="text-red-500 text-sm">✕</button>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <button onclick="updateQty(${idx}, -1)" class="qty-btn bg-gray-300 w-7 h-7 rounded">−</button>
                    <input type="number" value="${item.jumlah}"
                           onchange="setQty(${idx}, this.value)"
                           class="w-14 text-center border rounded font-bold text-sm mx-1 focus:ring-1 focus:ring-red-500 outline-none">
                    <button onclick="updateQty(${idx}, 1)" class="qty-btn bg-red-600 text-white w-7 h-7 rounded">+</button>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold">${unitLabel}</p>
                    <p class="text-[10px] text-gray-500">${numFormat(totalBotol)} botol</p>
                </div>
            </div>
        </div>`;
            });
            container.innerHTML = html;
            countSpan.innerText = cart.length;
            btn.disabled = false;
        }

        function clearCart() { if (confirm('Kosongkan keranjang?')) { cart = []; renderCart(); } }

        document.getElementById('form-rusak').addEventListener('submit', function(e) {
            if (!cart.length) { e.preventDefault(); alert('Keranjang kosong!'); return; }
            document.getElementById('form-cabang-asal').value = document.getElementById('select-cabang-asal').value;
            document.getElementById('cart-data').value = JSON.stringify(cart);
        });

        // Initialize
        loadStokCabang();
        renderCart();
    </script>

<?php include '../../includes/layout_footer.php'; ?>
