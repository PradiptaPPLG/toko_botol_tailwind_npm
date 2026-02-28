<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
ob_clean();

require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Check authentication
if (!is_login() || !is_admin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Custom safe query function for this API
function safe_query($sql) {
    global $conn;
    $result = $conn->query($sql);
    return $result;
}

function safe_escape($string) {
    global $conn;
    return $conn->real_escape_string($string);
}

try {
    $total_setor = (int)($_POST['total_setor'] ?? 0);
    $tanggal_dari = isset($_POST['tanggal_dari']) ? $_POST['tanggal_dari'] : date('Y-m-d');
    $tanggal_sampai = isset($_POST['tanggal_sampai']) ? $_POST['tanggal_sampai'] : date('Y-m-d');
    $cabang_id = (int)($_POST['cabang_id'] ?? 0);
    $keterangan = isset($_POST['keterangan']) ? trim($_POST['keterangan']) : '';

    // Validate input
    if ($total_setor <= 0) {
        echo json_encode(['success' => false, 'message' => 'Total setor harus lebih besar dari 0']);
        exit;
    }

    // Escape strings to prevent SQL injection
    $tanggal_dari = preg_replace('/[^\d\-]/', '', $tanggal_dari);
    $tanggal_sampai = preg_replace('/[^\d\-]/', '', $tanggal_sampai);
    $keterangan_escaped = safe_escape($keterangan);

    // Handle cabang_id: 0 means all cabangs (NULL in database)
    $cabang_id_value = ($cabang_id == 0) ? 'NULL' : $cabang_id;

    // Start transaction
    $trans_start = safe_query("START TRANSACTION");
    if (!$trans_start) {
        echo json_encode(['success' => false, 'message' => 'Gagal memulai transaksi: ' . $conn->error]);
        exit;
    }

    // Insert setoran record (cabang_id = NULL means all cabangs)
    $insert_query = "
        INSERT INTO setoran (cabang_id, total_setor, tanggal_dari, tanggal_sampai, keterangan, created_at)
        VALUES ($cabang_id_value, $total_setor, '$tanggal_dari', '$tanggal_sampai', '$keterangan_escaped', NOW())
    ";

    $insert_result = safe_query($insert_query);
    if (!$insert_result) {
        safe_query("ROLLBACK");
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan setoran: ' . $conn->error]);
        exit;
    }

    // Mark transactions as sudah_disetor = true
    if ($cabang_id == 0) {
        // Semua Cabang - update all cabangs
        $update_query = "
            UPDATE transaksi_header 
            SET sudah_disetor = 1
            WHERE DATE(created_at) BETWEEN '$tanggal_dari' AND '$tanggal_sampai'
              AND (sudah_disetor = 0 OR sudah_disetor IS NULL)
        ";
    } else {
        // Specific cabang
        $update_query = "
            UPDATE transaksi_header 
            SET sudah_disetor = 1
            WHERE cabang_id = $cabang_id
              AND DATE(created_at) BETWEEN '$tanggal_dari' AND '$tanggal_sampai'
              AND (sudah_disetor = 0 OR sudah_disetor IS NULL)
        ";
    }

    $update_result = safe_query($update_query);
    if (!$update_result) {
        safe_query("ROLLBACK");
        echo json_encode(['success' => false, 'message' => 'Gagal mengupdate transaksi: ' . $conn->error]);
        exit;
    }

    // Commit transaction
    $commit_result = safe_query("COMMIT");
    if (!$commit_result) {
        safe_query("ROLLBACK");
        echo json_encode(['success' => false, 'message' => 'Gagal melakukan commit: ' . $conn->error]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Setoran berhasil disimpan dan transaksi telah ditandai',
        'data' => [
            'total_setor' => $total_setor,
            'tanggal_dari' => $tanggal_dari,
            'tanggal_sampai' => $tanggal_sampai,
            'cabang_id' => $cabang_id
        ]
    ]);

} catch (Exception $e) {
    @safe_query("ROLLBACK");
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
}
