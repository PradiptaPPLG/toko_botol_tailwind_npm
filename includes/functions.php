
<?php
require_once 'database.php';

// ============= FUNGSI GET PRODUK =============
function get_produk() {
    return query("SELECT * FROM produk ORDER BY id DESC");
}

function get_produk_by_id($id) {
    $result = query("SELECT * FROM produk WHERE id = $id");
    return count($result) > 0 ? $result[0] : null;
}
function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function format_tanggal($tanggal) {
    return date('d/m/Y H:i', strtotime($tanggal));
}

function get_cabang() {
    return query("SELECT * FROM cabang ORDER BY id");
}

function get_stok_gudang($produk_id) {
    $result = query("SELECT stok_gudang FROM produk WHERE id = $produk_id");
    return count($result) > 0 ? $result[0]['stok_gudang'] : 0;
}

function get_stok_cabang($produk_id, $cabang_id) {
    $result = query("SELECT stok FROM stok_cabang WHERE produk_id = $produk_id AND cabang_id = $cabang_id");
    return count($result) > 0 ? $result[0]['stok'] : 0;
}

function cek_selisih_stok() {
    // This function now checks for actual stock discrepancies based on recent stock opname
    // Only shows warning if physical count (stok_fisik) is less than system (HILANG status)
    $warning = [];

    // Get recent stock opname with HILANG status (within last 7 days)
    $recent_so = query("
        SELECT so.*, p.nama_produk, p.stok_gudang
        FROM stock_opname so
        JOIN produk p ON so.produk_id = p.id
        WHERE so.status = 'HILANG'
        AND so.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY so.tanggal DESC
    ");

    foreach ($recent_so as $so) {
        $warning[] = [
            'produk' => $so['nama_produk'],
            'stok_gudang' => $so['stok_gudang'],
            'stok_sistem' => $so['stok_sistem'],
            'stok_fisik' => $so['stok_fisik'],
            'selisih' => $so['selisih'],
            'tanggal' => $so['tanggal']
        ];
    }

    return $warning;
}

function generate_invoice() {
    return 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
}
?>