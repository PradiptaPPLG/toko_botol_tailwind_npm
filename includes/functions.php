<?php
require_once 'database.php';

// ============= FUNGSI GET PRODUK =============
function get_produk($include_deleted = false): ?array
{
    if ($include_deleted) {
        return query("SELECT * FROM produk ORDER BY id DESC");
    } else {
        return query("SELECT * FROM produk WHERE status = 'active' ORDER BY id DESC");
    }
}

function get_produk_by_id($id) {
    $result = query("SELECT * FROM produk WHERE id = $id");
    return count($result) > 0 ? $result[0] : null;
}

function rupiah($angka): string
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function format_tanggal($tanggal): string
{
    return date('d/m/Y H:i', strtotime($tanggal));
}

function get_cabang(): ?array
{
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

// ============= FUNGSI CEK SELISIH STOK =============
function cek_selisih_stok(): array
{
    $warning = [];

    // Ambil data stock opname dengan status HILANG dalam 7 hari terakhir dan belum ditutup
    $recent_so = query("
        SELECT so.id, so.*, p.nama_produk, p.stok_gudang
        FROM stock_opname so
        JOIN produk p ON so.produk_id = p.id
        WHERE so.status = 'HILANG'
        AND so.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND so.is_hidden = 0
        ORDER BY so.tanggal DESC
    ");

    foreach ($recent_so as $so) {
        $warning[] = [
            'id'          => $so['id'],
            'produk'      => $so['nama_produk'],
            'stok_gudang' => $so['stok_gudang'],
            'stok_sistem' => $so['stok_sistem'],
            'stok_fisik'  => $so['stok_fisik'],
            'selisih'     => $so['selisih'],
            'tanggal'     => $so['tanggal']
        ];
    }

    return $warning;
}

function generate_invoice(): string
{
    return 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
}

// ============= FUNGSI TRANSAKSI HEADER-DETAIL =============
function save_transaction(array $header_data, array $items): array
{
    global $conn;

    try {
        $conn->begin_transaction();

        $no_invoice = $header_data['no_invoice'];
        $cabang_id = intval($header_data['cabang_id']);
        $session_kasir_id = isset($header_data['session_kasir_id']) && $header_data['session_kasir_id'] ? intval($header_data['session_kasir_id']) : 'NULL';
        $nama_kasir = escape_string($header_data['nama_kasir']);
        $tipe = $header_data['tipe'];
        $total_items = count($items);
        $total_harga = intval($header_data['total_harga']);
        $total_bayar = isset($header_data['total_bayar']) ? intval($header_data['total_bayar']) : 'NULL';
        $kembalian = isset($header_data['kembalian']) ? intval($header_data['kembalian']) : 'NULL';

        $sql_header = "INSERT INTO transaksi_header (no_invoice, cabang_id, session_kasir_id, nama_kasir, tipe, total_items, total_harga, total_bayar, kembalian)
                      VALUES ('$no_invoice', $cabang_id, $session_kasir_id, '$nama_kasir', '$tipe', $total_items, $total_harga, $total_bayar, $kembalian)";

        if (!execute($sql_header)) {
            throw new Exception('Failed to insert transaction header');
        }

        $header_id = last_insert_id();

        foreach ($items as $item) {
            $produk_id = intval($item['produk_id']);
            $produk_data = get_produk_by_id($produk_id);
            $nama_produk = escape_string($produk_data['nama_produk']);
            $jumlah = intval($item['jumlah']);
            $satuan = escape_string($item['satuan']);
            $harga_satuan = intval($item['harga_satuan']);
            $harga_tawar = isset($item['harga_tawar']) ? intval($item['harga_tawar']) : 'NULL';
            $selisih = isset($item['selisih']) ? intval($item['selisih']) : 'NULL';
            $subtotal = intval($item['subtotal']);

            $sql_detail = "INSERT INTO transaksi_detail (transaksi_header_id, no_invoice, produk_id, nama_produk, jumlah, satuan, harga_satuan, harga_tawar, selisih, subtotal)
                          VALUES ($header_id, '$no_invoice', $produk_id, '$nama_produk', $jumlah, '$satuan', $harga_satuan, $harga_tawar, $selisih, $subtotal)";

            if (!execute($sql_detail)) {
                throw new Exception('Failed to insert transaction detail for product: ' . $nama_produk);
            }

            $jumlah_botol = ($satuan === 'dus') ? ($jumlah * 12) : $jumlah;
            $stok_cabang = get_stok_cabang($produk_id, $cabang_id);

            if ($stok_cabang < $jumlah_botol) {
                throw new Exception('Stok tidak mencukupi untuk produk: ' . $nama_produk);
            }

            $sql_update_stok = "UPDATE stok_cabang SET stok = stok - $jumlah_botol
                               WHERE produk_id = $produk_id AND cabang_id = $cabang_id";

            if (!execute($sql_update_stok)) {
                throw new Exception('Failed to update stock for product: ' . $nama_produk);
            }
        }

        $conn->commit();

        return [
            'success' => true,
            'invoice' => $no_invoice,
            'header_id' => $header_id,
            'message' => "Transaksi berhasil! $total_items item diproses. Invoice: $no_invoice"
        ];

    } catch (Exception $e) {
        $conn->rollback();

        return [
            'success' => false,
            'invoice' => null,
            'header_id' => null,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

function get_transaction_by_invoice($no_invoice): ?array
{
    $header = query("SELECT * FROM transaksi_header WHERE no_invoice = '" . escape_string($no_invoice) . "'");
    if (empty($header)) {
        return null;
    }

    $header_data = $header[0];
    $details = query("SELECT * FROM transaksi_detail WHERE no_invoice = '" . escape_string($no_invoice) . "' ORDER BY id");

    return [
        'header' => $header_data,
        'details' => $details
    ];
}