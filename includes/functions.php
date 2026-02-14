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
    $produk = get_produk();
    $warning = [];
    
    foreach ($produk as $p) {
        $stok_gudang = $p['stok_gudang'];
        $total_stok_cabang = 0;
        
        $stok_cabang = query("SELECT SUM(stok) as total FROM stok_cabang WHERE produk_id = {$p['id']}");
        if (count($stok_cabang) > 0 && $stok_cabang[0]['total']) {
            $total_stok_cabang = $stok_cabang[0]['total'];
        }
        
        if ($total_stok_cabang > $stok_gudang) {
            $warning[] = [
                'produk' => $p['nama_produk'],
                'stok_gudang' => $stok_gudang,
                'stok_kasir' => $total_stok_cabang,
                'selisih' => $total_stok_cabang - $stok_gudang
            ];
        }
    }
    
    return $warning;
}

function generate_invoice() {
    return 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
}
?>