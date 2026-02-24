<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!is_login()) redirect('../../login.php');

// Ambil parameter op untuk mempertahankan tab setelah redirect
$current_op = isset($_GET['op']) ? $_GET['op'] : 'stok-masuk';

// ===== PROSES TUTUP NOTIFIKASI (DISMISS) =====
if (isset($_GET['dismiss']) && isset($_GET['id'])) {
    $so_id = intval($_GET['id']);
    execute("UPDATE stock_opname SET is_hidden = 1 WHERE id = $so_id");
    header("Location: index.php?op=$current_op");
    exit;
}

// Handler dismiss untuk riwayat stock opname
if (isset($_GET['dismiss_so']) && isset($_GET['id'])) {
    $so_id = intval($_GET['id']);
    execute("UPDATE stock_opname SET is_hidden = 1 WHERE id = $so_id");
    header("Location: index.php?op=$current_op");
    exit;
}

$title = 'Manajemen Gudang - ' . $_SESSION['user']['nama'];
include '../../includes/layout_header.php';
include '../../includes/layout_sidebar.php';

$produk = get_produk();
$cek_hilang = cek_selisih_stok();
$cabang = get_cabang();

// Include confirmation modal
include '../../includes/modal_confirm.php';

// Proses BATCH STOK MASUK
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

// Proses BATCH STOK KELUAR
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

// Stok opname
if (isset($_POST['stock_opname'])) {
    $produk_id = $_POST['produk_id'];
    $stok_fisik = intval($_POST['stok_fisik']);
    $petugas = escape_string($_POST['petugas']);
    $tanggal = $_POST['tanggal'];

    $stok_sistem = get_stok_gudang($produk_id);
    $selisih = $stok_sistem - $stok_fisik;
    $status = $selisih > 0 ? 'HILANG' : ($selisih < 0 ? 'LEBIH' : 'SESUAI');

    execute("INSERT INTO stock_opname (produk_id, stok_sistem, stok_fisik, selisih, status, petugas, tanggal, is_hidden)
             VALUES ($produk_id, $stok_sistem, $stok_fisik, $selisih, '$status', '$petugas', '$tanggal', 0)");
    $success_so = "✅ Stock Opname selesai! Selisih: $selisih botol ($status)";
}

// Pengeluaran
if (isset($_POST['tambah_pengeluaran'])) {
    $nominal = intval($_POST['nominal']);
    $keterangan = escape_string($_POST['keterangan']);
    execute("INSERT INTO pengeluaran (nominal, keterangan) VALUES ($nominal, '$keterangan')");
    $success_pengeluaran = "✅ Pengeluaran dicatat!";
}

$produk = get_produk();
$cek_hilang = cek_selisih_stok();
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
        .operation-toggle button.active {
            transform: scale(1.05);
        }
        .notif-item {
            position: relative;
            padding-right: 40px;
        }
        .close-notif {
            position: absolute;
            top: 10px;
            right: 15px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            z-index: 20;
            text-decoration: none;
            line-height: 1;
        }
        .close-notif:hover {
            background: rgba(255,255,255,0.4);
            transform: scale(1.1);
        }
        .close-notif-so {
            position: absolute;
            top: 5px;
            right: 10px;
            background: rgba(0,0,0,0.1);
            border: none;
            color: #666;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            text-decoration: none;
            line-height: 1;
            z-index: 10;
        }
        .close-notif-so:hover {
            background: rgba(0,0,0,0.2);
            color: #000;
        }
    </style>

    <div class="p-4 lg:p-6">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h1 class="judul text-2xl lg:text-3xl font-bold text-gray-800 flex items-center">
                <span class="mr-3">🏚️</span> MANAJEMEN GUDANG
            </h1>
            <p class="text-sm text-gray-600 mt-1">👤 <?= $_SESSION['user']['nama'] ?></p>
        </div>

        <!-- PERINGATAN -->
        <?php if (count($cek_hilang) > 0): ?>
            <div class="bg-red-600 text-white p-4 lg:p-6 rounded-lg mb-4 shadow-lg border-2 border-red-800">
                <div class="flex items-center text-xl lg:text-2xl font-bold mb-4">
                    <span class="text-4xl lg:text-5xl mr-4">⚠️</span>
                    <span>PERINGATAN! DITEMUKAN SELISIH STOK (7 HARI TERAKHIR)</span>
                </div>

                <?php foreach ($cek_hilang as $w): ?>
                    <div class="bg-red-700 p-3 lg:p-4 mb-3 rounded text-sm lg:text-lg notif-item" id="notif-<?= $w['id'] ?>">
                        <!-- Tombol Close dengan parameter op -->
                        <a href="?dismiss=1&id=<?= $w['id'] ?>&op=<?= $current_op ?>" class="close-notif" onclick="return confirm('Tutup notifikasi ini?')" title="Tutup notifikasi">&times;</a>

                        <div class="flex justify-between items-center mb-2">
                            <span class="font-bold text-lg lg:text-xl"><?= $w['produk'] ?></span>
                            <span class="text-xs lg:text-sm"><?= date('d/m/Y', strtotime($w['tanggal'])) ?></span>
                        </div>
                        <div class="flex justify-between items-center flex-wrap gap-2">
                            <span>Stok Sistem: <?= $w['stok_sistem'] ?> botol</span>
                            <span>Stok Fisik: <?= $w['stok_fisik'] ?> botol</span>
                            <span class="bg-red-800 px-3 lg:px-4 py-1 lg:py-2 rounded-full text-white font-bold text-sm lg:text-base">
                            HILANG <?= $w['selisih'] ?> BOTOL
                        </span>
                        </div>
                    </div>
                <?php endforeach; ?>

                <p class="text-center mt-4 text-sm lg:text-lg">
                    📋 Berdasarkan Stock Opname terakhir. Lakukan pengecekan lebih lanjut.
                </p>
            </div>
        <?php else: ?>
            <div class="bg-green-100 border-l-4 lg:border-l-8 border-green-500 text-green-800 p-4 lg:p-6 mb-4 rounded-lg shadow">
                <div class="flex items-center text-base lg:text-xl">
                    <span class="text-3xl lg:text-4xl mr-4">✅</span>
                    <span class="font-bold">Tidak ada selisih stok dari stock opname 7 hari terakhir.</span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Success Messages -->
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded"><?= $success ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">❌ <?= $error ?></div>
        <?php endif; ?>
        <?php if (isset($success_so)): ?>
            <div class="bg-purple-100 border-l-4 border-purple-500 text-purple-700 p-4 mb-4 rounded"><?= $success_so ?></div>
        <?php endif; ?>
        <?php if (isset($success_pengeluaran)): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4 rounded"><?= $success_pengeluaran ?></div>
        <?php endif; ?>

        <!-- Operation Mode Selector -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h2 class="text-lg font-bold mb-3">Pilih Operasi:</h2>
            <div class="operation-toggle grid grid-cols-2 md:grid-cols-4 gap-2">
                <button onclick="showOperation('stok-masuk')" id="btn-stok-masuk"
                        class="active py-3 px-4 rounded-lg font-bold text-white bg-green-600 hover:bg-green-700">
                    📥 STOK MASUK
                </button>
                <button onclick="showOperation('stok-keluar')" id="btn-stok-keluar"
                        class="py-3 px-4 rounded-lg font-bold bg-gray-200 text-gray-700 hover:bg-gray-300">
                    📤 STOK KELUAR
                </button>
                <button onclick="showOperation('stock-opname')" id="btn-stock-opname"
                        class="py-3 px-4 rounded-lg font-bold bg-gray-200 text-gray-700 hover:bg-gray-300">
                    📋 STOCK OPNAME
                </button>
                <button onclick="showOperation('pengeluaran')" id="btn-pengeluaran"
                        class="py-3 px-4 rounded-lg font-bold bg-gray-200 text-gray-700 hover:bg-gray-300">
                    💸 PENGELUARAN
                </button>
            </div>
        </div>

        <!-- STOK MASUK Section -->
        <div id="section-stok-masuk" class="operation-section">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Product Grid -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow p-4">
                        <h2 class="text-xl font-bold mb-4">🥤 Pilih Produk untuk Stok Masuk</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                            <?php foreach ($produk as $p): ?>
                                <div class="product-card bg-white border-2 border-gray-200 rounded-lg p-3"
                                     onclick="addToCartMasuk(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_produk']) ?>', <?= $p['stok_gudang'] ?>)">
                                    <div class="bg-linear-to-br from-green-50 to-green-100 rounded-lg h-20 flex items-center justify-center mb-2">
                                        <span class="text-3xl">🥤</span>
                                    </div>
                                    <h3 class="font-bold text-sm mb-1 truncate"><?= $p['nama_produk'] ?></h3>
                                    <p class="text-xs text-gray-600">Stok Gudang: <?= $p['stok_gudang'] ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Cart Sidebar -->
                <div class="lg:col-span-1">
                    <div class="cart-sidebar bg-white rounded-lg shadow p-4">
                        <h2 class="text-xl font-bold mb-4 text-green-700">📥 Keranjang Stok Masuk</h2>

                        <div id="cart-masuk-items" class="space-y-2 mb-4 max-h-96 overflow-y-auto">
                            <p class="text-gray-400 text-center py-8 text-sm">Keranjang kosong</p>
                        </div>

                        <div class="border-t pt-4 space-y-3">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total Item</span>
                                <span id="cart-masuk-count" class="text-green-600">0</span>
                            </div>

                            <form method="POST" id="form-stok-masuk">
                                <input type="hidden" name="batch_stok_masuk" value="1">
                                <input type="hidden" name="cart_data" id="cart-masuk-data">

                                <div class="mb-3">
                                    <label class="block text-sm font-medium mb-2">Keterangan (Opsional)</label>
                                    <input type="text" name="keterangan" class="w-full border rounded-lg p-2 text-sm"
                                           placeholder="Dari supplier X...">
                                </div>

                                <button type="submit" id="btn-submit-masuk"
                                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                                        disabled>
                                    ✅ PROSES STOK MASUK
                                </button>
                            </form>

                            <button type="button" onclick="clearCartMasuk()"
                                    class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-lg text-sm">
                                🗑️ Kosongkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STOK KELUAR Section -->
        <div id="section-stok-keluar" class="operation-section hidden">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Product Grid -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow p-4">
                        <h2 class="text-xl font-bold mb-4">🥤 Pilih Produk untuk Stok Keluar</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                            <?php foreach ($produk as $p): ?>
                                <?php $disabled = $p['stok_gudang'] <= 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>
                                <div class="product-card bg-white border-2 border-gray-200 rounded-lg p-3 <?= $disabled ?>"
                                     onclick="<?= $p['stok_gudang'] > 0 ? 'addToCartKeluar('.$p['id'].', \''.htmlspecialchars($p['nama_produk']).'\', '.$p['stok_gudang'].')' : '' ?>">
                                    <div class="bg-linear-to-br from-yellow-50 to-yellow-100 rounded-lg h-20 flex items-center justify-center mb-2">
                                        <span class="text-3xl">🥤</span>
                                    </div>
                                    <h3 class="font-bold text-sm mb-1 truncate"><?= $p['nama_produk'] ?></h3>
                                    <p class="text-xs <?= $p['stok_gudang'] > 0 ? 'text-green-600' : 'text-red-600' ?>">
                                        Stok: <?= $p['stok_gudang'] ?> btl
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Cart Sidebar -->
                <div class="lg:col-span-1">
                    <div class="cart-sidebar bg-white rounded-lg shadow p-4">
                        <h2 class="text-xl font-bold mb-4 text-yellow-700">📤 Keranjang Stok Keluar</h2>

                        <!-- Kondisi Toggle -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Tipe:</label>
                            <div class="flex gap-2">
                                <button type="button" onclick="setKondisi('rusak')" id="btn-rusak"
                                        class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold bg-red-600 text-white">
                                    🔴 Rusak
                                </button>
                                <button type="button" onclick="setKondisi('transfer')" id="btn-transfer"
                                        class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold bg-gray-200 text-gray-700">
                                    🔄 Transfer
                                </button>
                            </div>
                        </div>

                        <!-- Cabang Tujuan -->
                        <div id="div-cabang-tujuan" class="mb-4 hidden">
                            <label class="block text-sm font-medium mb-2">Tujuan Cabang:</label>
                            <select name="cabang_tujuan" id="select-cabang" class="w-full border rounded-lg p-2 text-sm">
                                <?php foreach ($cabang as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="cart-keluar-items" class="space-y-2 mb-4 max-h-64 overflow-y-auto">
                            <p class="text-gray-400 text-center py-8 text-sm">Keranjang kosong</p>
                        </div>

                        <div class="border-t pt-4 space-y-3">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total Item</span>
                                <span id="cart-keluar-count" class="text-yellow-600">0</span>
                            </div>

                            <form method="POST" id="form-stok-keluar">
                                <input type="hidden" name="batch_stok_keluar" value="1">
                                <input type="hidden" name="cart_data" id="cart-keluar-data">
                                <input type="hidden" name="kondisi" id="form-kondisi" value="rusak">
                                <input type="hidden" name="cabang_tujuan" id="form-cabang-tujuan">

                                <div class="mb-3">
                                    <label class="block text-sm font-medium mb-2">Keterangan (Opsional)</label>
                                    <textarea name="keterangan" rows="2" class="w-full border rounded-lg p-2 text-sm"
                                              placeholder="Keterangan..."></textarea>
                                </div>

                                <button type="submit" id="btn-submit-keluar"
                                        class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-3 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                                        disabled>
                                    ✅ PROSES STOK KELUAR
                                </button>
                            </form>

                            <button type="button" onclick="clearCartKeluar()"
                                    class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-lg text-sm">
                                🗑️ Kosongkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STOCK OPNAME Section -->
        <div id="section-stock-opname" class="operation-section hidden">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-2xl font-bold text-purple-700 mb-4 flex items-center">
                    <span class="mr-2">📋</span> STOCK OPNAME - HITUNG STOK FISIK
                </h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <form method="POST">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Produk</label>
                                    <select name="produk_id" required class="w-full border rounded-lg p-3">
                                        <?php foreach ($produk as $p): ?>
                                            <option value="<?= $p['id'] ?>"><?= $p['nama_produk'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Stok Fisik</label>
                                    <input type="number" name="stok_fisik" min="0" required class="w-full border rounded-lg p-3">
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Petugas</label>
                                    <input type="text" name="petugas" required class="w-full border rounded-lg p-3" value="<?= $_SESSION['user']['nama'] ?>">
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Tanggal</label>
                                    <input type="date" name="tanggal" required class="w-full border rounded-lg p-3" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                            <button type="submit" name="stock_opname" class="mt-4 w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg text-lg">
                                🔍 HITUNG SELISIH
                            </button>
                        </form>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-bold text-lg mb-3">📊 Riwayat Stock Opname</h3>
                        <?php
                        $so_history = query("SELECT so.*, p.nama_produk FROM stock_opname so JOIN produk p ON so.produk_id = p.id WHERE so.is_cancelled = 0 ORDER BY so.created_at DESC LIMIT 10");
                        ?>
                        <?php if (count($so_history) > 0): ?>
                            <div class="space-y-2 max-h-80 overflow-y-auto">
                                <?php foreach ($so_history as $so): ?>
                                    <div class="bg-white p-3 rounded shadow-sm relative <?= $so['status'] == 'HILANG' ? 'border-l-4 border-red-500' : 'border-l-4 border-green-500' ?>" style="position: relative;">
                                        <!-- Tombol X dengan parameter op -->
                                        <a href="?dismiss_so=1&id=<?= $so['id'] ?>&op=<?= $current_op ?>" class="close-notif-so" onclick="return confirm('Hapus riwayat ini?')" title="Hapus">&times;</a>

                                        <div class="flex justify-between">
                                            <span class="font-bold"><?= $so['nama_produk'] ?></span>
                                        </div>
                                        <div class="flex justify-between mt-1">
                                            <span>Sistem: <?= $so['stok_sistem'] ?> | Fisik: <?= $so['stok_fisik'] ?></span>
                                            <span class="font-bold <?= $so['selisih'] > 0 ? 'text-red-600' : 'text-green-600' ?>">
                                            <?= $so['selisih'] > 0 ? 'HILANG ' . $so['selisih'] : ($so['selisih'] < 0 ? 'LEBIH ' . abs($so['selisih']) : 'SESUAI') ?>
                                        </span>
                                        </div>
                                        <span class="text-sm"><?= date('d/m/Y', strtotime($so['tanggal'])) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-4">Belum ada stock opname</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- PENGELUARAN Section -->
        <div id="section-pengeluaran" class="operation-section hidden">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-2xl font-bold text-red-700 mb-4 flex items-center">
                    <span class="mr-2">💸</span> PENGELUARAN OPERASIONAL
                </h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
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
                                <button type="submit" name="tambah_pengeluaran" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg text-lg">
                                    💰 CATAT PENGELUARAN
                                </button>
                            </div>
                        </form>
                    </div>

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
        </div>
    </div>

    <script>
        // Navigation
        function showOperation(operation) {
            // Hide all sections
            document.querySelectorAll('.operation-section').forEach(s => s.classList.add('hidden'));

            // Remove active class from all buttons
            document.querySelectorAll('.operation-toggle button').forEach(b => {
                b.classList.remove('active', 'bg-green-600', 'bg-yellow-600', 'bg-purple-600', 'bg-red-600', 'text-white');
                b.classList.add('bg-gray-200', 'text-gray-700');
            });

            // Show selected section
            document.getElementById('section-' + operation).classList.remove('hidden');

            // Activate button
            const btn = document.getElementById('btn-' + operation);
            btn.classList.remove('bg-gray-200', 'text-gray-700');
            btn.classList.add('active', 'text-white');

            if (operation === 'stok-masuk') btn.classList.add('bg-green-600');
            else if (operation === 'stok-keluar') btn.classList.add('bg-yellow-600');
            else if (operation === 'stock-opname') btn.classList.add('bg-purple-600');
            else if (operation === 'pengeluaran') btn.classList.add('bg-red-600');
        }

        // ============= STOK MASUK CART =============
        let cartMasuk = [];

        function addToCartMasuk(id, nama, stokGudang) {
            const existing = cartMasuk.find(item => item.produk_id === id);

            if (existing) {
                existing.jumlah++;
            } else {
                cartMasuk.push({
                    produk_id: id,
                    nama: nama,
                    jumlah: 1,
                    stok_gudang: stokGudang
                });
            }

            renderCartMasuk();
        }

        function updateQtyMasuk(index, change) {
            cartMasuk[index].jumlah += change;

            if (cartMasuk[index].jumlah <= 0) {
                cartMasuk.splice(index, 1);
            }

            renderCartMasuk();
        }

        function renderCartMasuk() {
            const container = document.getElementById('cart-masuk-items');
            const count = document.getElementById('cart-masuk-count');
            const btn = document.getElementById('btn-submit-masuk');

            if (cartMasuk.length === 0) {
                container.innerHTML = '<p class="text-gray-400 text-center py-8 text-sm">Keranjang kosong</p>';
                count.textContent = '0';
                btn.disabled = true;
                return;
            }

            let html = '';
            cartMasuk.forEach((item, index) => {
                html += `
            <div class="border rounded-lg p-2 bg-gray-50">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1">
                        <h4 class="font-semibold text-sm">${item.nama}</h4>
                        <p class="text-xs text-gray-600">Stok: ${item.stok_gudang} btl</p>
                    </div>
                    <button onclick="cartMasuk.splice(${index}, 1); renderCartMasuk();"
                            class="text-red-500 hover:text-red-700 text-sm">✕</button>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="updateQtyMasuk(${index}, -1)"
                            class="qty-btn bg-gray-300 hover:bg-gray-400 w-7 h-7 rounded flex items-center justify-center font-bold">−</button>
                    <span class="font-bold w-12 text-center">${item.jumlah}</span>
                    <button onclick="updateQtyMasuk(${index}, 1)"
                            class="qty-btn bg-green-600 hover:bg-green-700 text-white w-7 h-7 rounded flex items-center justify-center font-bold">+</button>
                    <span class="text-xs text-gray-500 ml-auto">botol</span>
                </div>
            </div>
        `;
            });

            container.innerHTML = html;
            count.textContent = cartMasuk.length;
            btn.disabled = false;
        }

        function clearCartMasuk() {
            if (confirm('Kosongkan keranjang?')) {
                cartMasuk = [];
                renderCartMasuk();
            }
        }

        document.getElementById('form-stok-masuk').addEventListener('submit', function(e) {
            if (cartMasuk.length === 0) {
                e.preventDefault();
                alert('Keranjang masih kosong!');
                return;
            }
            document.getElementById('cart-masuk-data').value = JSON.stringify(cartMasuk);
        });

        // ============= STOK KELUAR CART =============
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
            const existing = cartKeluar.find(item => item.produk_id === id);

            if (existing) {
                if (existing.jumlah < stokGudang) {
                    existing.jumlah++;
                } else {
                    alert('Jumlah melebihi stok yang tersedia!');
                    return;
                }
            } else {
                cartKeluar.push({
                    produk_id: id,
                    nama: nama,
                    jumlah: 1,
                    stok_gudang: stokGudang
                });
            }

            renderCartKeluar();
        }

        function updateQtyKeluar(index, change) {
            const newQty = cartKeluar[index].jumlah + change;

            if (newQty <= 0) {
                cartKeluar.splice(index, 1);
            } else if (newQty > cartKeluar[index].stok_gudang) {
                alert('Jumlah melebihi stok yang tersedia!');
                return;
            } else {
                cartKeluar[index].jumlah = newQty;
            }

            renderCartKeluar();
        }

        function renderCartKeluar() {
            const container = document.getElementById('cart-keluar-items');
            const count = document.getElementById('cart-keluar-count');
            const btn = document.getElementById('btn-submit-keluar');

            if (cartKeluar.length === 0) {
                container.innerHTML = '<p class="text-gray-400 text-center py-8 text-sm">Keranjang kosong</p>';
                count.textContent = '0';
                btn.disabled = true;
                return;
            }

            let html = '';
            cartKeluar.forEach((item, index) => {
                html += `
            <div class="border rounded-lg p-2 bg-gray-50">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1">
                        <h4 class="font-semibold text-sm">${item.nama}</h4>
                        <p class="text-xs text-gray-600">Max: ${item.stok_gudang} btl</p>
                    </div>
                    <button onclick="cartKeluar.splice(${index}, 1); renderCartKeluar();"
                            class="text-red-500 hover:text-red-700 text-sm">✕</button>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="updateQtyKeluar(${index}, -1)"
                            class="qty-btn bg-gray-300 hover:bg-gray-400 w-7 h-7 rounded flex items-center justify-center font-bold">−</button>
                    <span class="font-bold w-12 text-center">${item.jumlah}</span>
                    <button onclick="updateQtyKeluar(${index}, 1)"
                            class="qty-btn bg-yellow-600 hover:bg-yellow-700 text-white w-7 h-7 rounded flex items-center justify-center font-bold">+</button>
                    <span class="text-xs text-gray-500 ml-auto">botol</span>
                </div>
            </div>
        `;
            });

            container.innerHTML = html;
            count.textContent = cartKeluar.length;
            btn.disabled = false;
        }

        function clearCartKeluar() {
            if (confirm('Kosongkan keranjang?')) {
                cartKeluar = [];
                renderCartKeluar();
            }
        }

        document.getElementById('form-stok-keluar').addEventListener('submit', function(e) {
            if (cartKeluar.length === 0) {
                e.preventDefault();
                alert('Keranjang masih kosong!');
                return;
            }

            if (kondisi === 'transfer') {
                document.getElementById('form-cabang-tujuan').value = document.getElementById('select-cabang').value;
            }

            document.getElementById('cart-keluar-data').value = JSON.stringify(cartKeluar);
        });

        // Initial render
        renderCartMasuk();
        renderCartKeluar();

        // Buka tab berdasarkan parameter URL
        const urlParams = new URLSearchParams(window.location.search);
        const op = urlParams.get('op');
        if (op) {
            showOperation(op);
        } else {
            // Default ke stok-masuk jika tidak ada parameter
            showOperation('stok-masuk');
        }
    </script>

<?php include '../../includes/layout_footer.php'; ?>