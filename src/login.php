<?php
require_once 'includes/auth.php';

if (is_login()) {
    if (is_admin()) {
        redirect('dashboard.php');
    } else {
        redirect('modules/kasir/index.php');
    }
}

$error = '';
$active_tab = $_GET['tab'] ?? 'kasir';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login_kasir'])) {
        $nama = trim($_POST['nama_kasir']);
        $cabang_id = $_POST['cabang_id'];
        
        if (!empty($nama) && !empty($cabang_id)) {
            login_kasir($nama, $cabang_id);
            redirect('modules/kasir/index.php'); // <=== INI BENAR!
        } else {
            $error = 'Nama dan cabang harus diisi!';
        }
    }
    
    if (isset($_POST['login_admin'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        if (login_admin($username, $password)) {
            redirect('dashboard.php'); // <=== INI SUDAH BENAR
        } else {
            $error = 'Username atau password salah!';
        }
    }
}

$cabang = query("SELECT * FROM cabang");
?>
<?php include 'includes/layout_header.php'; ?>
<body class="bg-linear-to-br from-blue-900 to-blue-700">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-800 mb-2">🥤 TOKO PDK</h1>
                <p class="text-gray-600">Sistem Kasir & Gudang</p>
            </div>
            
            <!-- Tab Navigation -->
            <div class="flex mb-6 border-b">
                <button onclick="showTab('kasir')" class="flex-1 py-3 text-center font-medium <?= $active_tab == 'kasir' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500' ?>" id="tab-kasir-btn">
                    👤 Kasir
                </button>
                <button onclick="showTab('admin')" class="flex-1 py-3 text-center font-medium <?= $active_tab == 'admin' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500' ?>" id="tab-admin-btn">
                    🔐 Admin
                </button>
            </div>
            
            <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <p class="font-medium">⚠️ <?= $error ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Form Kasir -->
            <div id="tab-kasir" class="<?= $active_tab != 'kasir' ? 'hidden' : '' ?>">
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">👤 Nama Kasir</label>
                        <label>
                            <input type="text" name="nama_kasir" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg" placeholder="Masukkan nama Anda">
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">📍 Pilih Cabang</label>
                        <label>
                            <select name="cabang_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg">
                                <option value="">-- Pilih Cabang --</option>
                                <?php foreach ($cabang as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    
                    <button type="submit" name="login_kasir" class="w-full btn-primary text-white font-bold py-4 px-4 rounded-lg text-lg">
                        🚀 MASUK KASIR
                    </button>
                </form>
            </div>
            
            <!-- Form Admin -->
            <div id="tab-admin" class="<?= $active_tab != 'admin' ? 'hidden' : '' ?>">
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">🔑 Username</label>
                        <label>
                            <input type="text" name="username" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg" placeholder="Masukkan username">
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">🔒 Password</label>
                        <label>
                            <input type="password" name="password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg" placeholder="Masukkan password">
                        </label>
                    </div>
                    
                    <button type="submit" name="login_admin" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-bold py-4 px-4 rounded-lg text-lg">
                        🔐 MASUK ADMIN
                    </button>
                </form>
            </div>
            
            <div class="mt-8 text-center text-sm text-gray-500">
                <p>⚠️ Untuk demo: Admin (admin / @admin123)</p>
                <p>Kasir (cukup isi nama & cabang)</p>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tab) {
            document.getElementById('tab-kasir').classList.add('hidden');
            document.getElementById('tab-admin').classList.add('hidden');
            document.getElementById('tab-' + tab).classList.remove('hidden');
            
            document.getElementById('tab-kasir-btn').classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
            document.getElementById('tab-admin-btn').classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
            document.getElementById('tab-' + tab + '-btn').classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
        }
    </script>
</body>
</html>
