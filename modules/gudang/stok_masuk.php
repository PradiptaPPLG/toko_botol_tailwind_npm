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
            $produk_id   = intval($item['produk_id']);
            $satuan      = $item['satuan'] ?? 'dus';
            $jumlah      = intval($item['jumlah']);
            $harga_input = (float)($item['harga_beli'] ?? 0);
            
            if ($satuan === 'dus') {
                $jumlah_botol = $jumlah * 12;
                $harga_beli_satuan = $harga_input / 12; 
                $total_item = $jumlah * $harga_input;
            } else {
                $jumlah_botol = $jumlah;
                $harga_beli_satuan = $harga_input;
                $total_item = $jumlah * $harga_input;
            }

            execute("UPDATE produk SET stok_gudang = stok_gudang + $jumlah_botol, harga_beli = $harga_beli_satuan WHERE id = $produk_id");
            execute("INSERT INTO stok_masuk (produk_id, jumlah, harga_beli_satuan, total_belanja, keterangan, batch_id)
                     VALUES ($produk_id, $jumlah_botol, $harga_beli_satuan, $total_item, '$keterangan', '$batch_id')");
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
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">🥤 Pilih Produk</h2>
                        <div class="flex bg-gray-100 rounded-lg p-1">
                            <button onclick="setGlobalUnit('botol')" id="unit-botol" class="px-3 py-1 rounded text-xs font-semibold text-gray-500">Botol</button>
                            <button onclick="setGlobalUnit('dus')" id="unit-dus" class="px-3 py-1 rounded text-xs font-semibold bg-white text-green-600">Dus</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                        <?php foreach ($produk as $p): ?>
                            <div class="product-card bg-white border-2 border-gray-200 rounded-lg p-3"
                                 onclick="addToCartMasuk(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_produk']) ?>', <?= $p['stok_gudang'] ?>, <?= $p['harga_beli'] ?? 0 ?>)">
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
                    <div class="flex justify-between text-lg font-bold mb-1">
                            <span>Total Item</span>
                            <span id="cart-masuk-count" class="text-green-600">0</span>
                        </div>
                        <div class="flex justify-between text-sm font-semibold mb-3 text-indigo-700">
                            <span>Total Belanja</span>
                            <span id="cart-masuk-total">Rp 0</span>
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
        let globalUnit = 'dus';

        function setGlobalUnit(unit) {
            globalUnit = unit;
            const btnBotol = document.getElementById('unit-botol');
            const btnDus = document.getElementById('unit-dus');
            
            if(unit === 'botol') {
                btnBotol.className = 'px-3 py-1 rounded text-xs font-semibold bg-white text-green-600';
                btnDus.className = 'px-3 py-1 rounded text-xs font-semibold text-gray-500';
            } else {
                btnBotol.className = 'px-3 py-1 rounded text-xs font-semibold text-gray-500';
                btnDus.className = 'px-3 py-1 rounded text-xs font-semibold bg-white text-green-600';
            }
        }

        function addToCartMasuk(id, nama, stokGudang, hargaBeli) {
            const isDus = globalUnit === 'dus';
            // Update modal labels
            document.getElementById('harga-modal-title').textContent = isDus ? '📦 Input Stok Masuk (DUS)' : '🥤 Input Stok Masuk (BOTOL)';
            document.getElementById('harga-modal-nama').textContent = nama;
            document.getElementById('jumlah-modal-label').textContent = isDus ? 'Jumlah Dus' : 'Jumlah Botol';
            document.getElementById('harga-modal-label').textContent = isDus ? 'Harga Beli per DUS (Rp)' : 'Harga Beli per Botol (Rp)';
            document.getElementById('jumlah-modal-hint').textContent = isDus ? '* 1 dus = 12 botol' : '';
            document.getElementById('jumlah-modal-input').value = '1';
            document.getElementById('harga-modal-input').value = '';
            
            document.getElementById('harga-modal-confirm').onclick = function() {
                const jumlah = parseInt(document.getElementById('jumlah-modal-input').value) || 0;
                const harga = parseFloat(document.getElementById('harga-modal-input').value) || 0;
                
                if (jumlah <= 0) { alert('Jumlah harus lebih dari 0'); return; }
                
                document.getElementById('hargaBeliModal').classList.add('hidden');

                const satuan = globalUnit;
                let item = cartMasuk.find(i => i.produk_id === id && i.satuan === satuan);
                if (item) {
                    item.jumlah += jumlah;
                    item.harga_beli = harga || item.harga_beli;
                } else {
                    cartMasuk.push({ 
                        produk_id: id, 
                        nama, 
                        jumlah: jumlah, 
                        stok_gudang: stokGudang, 
                        harga_beli: harga,
                        satuan: satuan
                    });
                }
                renderCartMasuk();
            };
            document.getElementById('hargaBeliModal').classList.remove('hidden');
            document.getElementById('jumlah-modal-input').focus();
            document.getElementById('jumlah-modal-input').select();
        }

        function updateQtyMasuk(index, delta) {
            cartMasuk[index].jumlah += delta;
            if (cartMasuk[index].jumlah <= 0) cartMasuk.splice(index, 1);
            renderCartMasuk();
        }

        function setQtyMasuk(index, value) {
            let newQty = parseInt(value) || 0;
            if (newQty <= 0) {
                cartMasuk.splice(index, 1);
            } else {
                cartMasuk[index].jumlah = newQty;
            }
            renderCartMasuk();
        }

        function editHargaBeli(index) {
            const item = cartMasuk[index];
            const isDus = item.satuan === 'dus';
            document.getElementById('harga-modal-title').textContent = isDus ? '📦 Input Stok Masuk (DUS)' : '🥤 Input Stok Masuk (BOTOL)';
            document.getElementById('harga-modal-nama').textContent = item.nama;
            document.getElementById('jumlah-modal-label').textContent = isDus ? 'Jumlah Dus' : 'Jumlah Botol';
            document.getElementById('harga-modal-label').textContent = isDus ? 'Harga Beli per DUS (Rp)' : 'Harga Beli per Botol (Rp)';
            document.getElementById('jumlah-modal-hint').textContent = isDus ? '* 1 dus = 12 botol' : '';
            document.getElementById('jumlah-modal-input').value = item.jumlah;
            document.getElementById('harga-modal-input').value = item.harga_beli || '';
            
            document.getElementById('harga-modal-confirm').onclick = function() {
                const jumlah = parseInt(document.getElementById('jumlah-modal-input').value) || 0;
                const harga = parseFloat(document.getElementById('harga-modal-input').value) || 0;
                
                if (jumlah <= 0) { alert('Jumlah harus lebih dari 0'); return; }
                
                cartMasuk[index].jumlah = jumlah;
                cartMasuk[index].harga_beli = harga;
                
                document.getElementById('hargaBeliModal').classList.add('hidden');
                renderCartMasuk();
            };
            document.getElementById('hargaBeliModal').classList.remove('hidden');
            document.getElementById('jumlah-modal-input').focus();
            document.getElementById('jumlah-modal-input').select();
        }

        function renderCartMasuk() {
            let container = document.getElementById('cart-masuk-items');
            let countSpan = document.getElementById('cart-masuk-count');
            let totalSpan = document.getElementById('cart-masuk-total');
            let btn = document.getElementById('btn-submit-masuk');
            if (!cartMasuk.length) {
                container.innerHTML = '<p class="text-gray-400 text-center py-8">Keranjang kosong</p>';
                countSpan.innerText = '0';
                totalSpan.innerText = 'Rp 0';
                btn.disabled = true;
                return;
            }
            let totalBelanja = 0;
            let html = '';
            cartMasuk.forEach((item, idx) => {
                const subtotal = item.jumlah * (item.harga_beli || 0);
                const isDus = item.satuan === 'dus';
                const totalBotol = isDus ? item.jumlah * 12 : item.jumlah;
                const unitLabel = isDus ? 'dus' : 'botol';
                const hargaLabel = isDus ? 'Harga Beli/dus' : 'Harga Beli/botol';
                totalBelanja += subtotal;
                html += `<div class="border rounded-lg p-2 bg-gray-50">
            <div class="flex justify-between items-start mb-1">
                <div><h4 class="font-semibold text-sm">${item.nama}</h4><p class="text-xs text-gray-600">Stok: ${item.stok_gudang} btl</p></div>
                <button onclick="cartMasuk.splice(${idx},1); renderCartMasuk();" class="text-red-500 text-sm">✕</button>
            </div>
            <div class="flex items-center gap-1 mb-1">
                <span class="text-xs text-gray-500 mr-1">${hargaLabel}:</span>
                <span class="text-xs font-bold text-indigo-700">${formatRp(item.harga_beli)}</span>
                <button onclick="editHargaBeli(${idx})" class="ml-auto text-xs bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-2 py-0.5 rounded">✏️ Ubah</button>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <button onclick="updateQtyMasuk(${idx}, -1)" class="qty-btn bg-gray-300 w-7 h-7 rounded">−</button>
                    <input type="number" value="${item.jumlah}" 
                           onchange="setQtyMasuk(${idx}, this.value)"
                           class="w-16 text-center border rounded font-bold text-sm mx-1 focus:ring-1 focus:ring-green-500 outline-none">
                    <button onclick="updateQtyMasuk(${idx}, 1)" class="qty-btn bg-green-600 text-white w-7 h-7 rounded">+</button>
                    <span class="text-xs ml-1">${unitLabel}</span>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-gray-500">${numFormat(totalBotol)} botol</p>
                    <p class="text-xs font-bold text-green-700">${formatRp(subtotal)}</p>
                </div>
            </div>
        </div>`;
            });
            container.innerHTML = html;
            countSpan.innerText = cartMasuk.length;
            totalSpan.innerText = formatRp(totalBelanja);
            btn.disabled = false;
        }

        function formatRp(n) {
            return 'Rp ' + parseFloat(n || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function numFormat(n) {
            return parseInt(n || 0).toLocaleString('id-ID');
        }

        async function clearCartMasuk() {
            const ok = await confirmClear('Kosongkan semua item di keranjang?');
            if (ok) { cartMasuk = []; renderCartMasuk(); }
        }

        document.getElementById('form-stok-masuk').addEventListener('submit', function(e) {
            if (!cartMasuk.length) { e.preventDefault(); alert('Keranjang kosong!'); return; }
            document.getElementById('cart-masuk-data').value = JSON.stringify(cartMasuk);
        });

        // Close harga modal on outside click
        document.getElementById('hargaBeliModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });
        // Enter key submits modal
        document.getElementById('harga-modal-input').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') document.getElementById('harga-modal-confirm').click();
        });

        renderCartMasuk();
    </script>

<!-- Harga Beli Modal -->
<div id="hargaBeliModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-gray-800 mb-1" id="harga-modal-title">📦 Input Stok Masuk (DUS)</h3>
        <p class="text-sm text-gray-500 mb-4" id="harga-modal-nama"></p>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1" id="jumlah-modal-label">Jumlah Dus</label>
            <input type="number" id="jumlah-modal-input" placeholder="Contoh: 10"
                   class="w-full border-2 border-indigo-300 rounded-lg p-3 text-lg focus:outline-none focus:border-indigo-500">
            <p class="text-[10px] text-indigo-600 mt-1" id="jumlah-modal-hint">* 1 dus = 12 botol</p>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1" id="harga-modal-label">Harga Beli per DUS (Rp)</label>
            <input type="number" id="harga-modal-input" placeholder="Contoh: 30000"
                   class="w-full border-2 border-indigo-300 rounded-lg p-3 text-lg focus:outline-none focus:border-indigo-500">
        </div>

        <div class="flex gap-3">
            <button onclick="document.getElementById('hargaBeliModal').classList.add('hidden')"
                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 rounded-lg">
                Batal
            </button>
            <button id="harga-modal-confirm"
                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded-lg">
                ✅ Tambah
            </button>
        </div>
    </div>
</div>

<?php include '../../includes/layout_footer.php'; ?>