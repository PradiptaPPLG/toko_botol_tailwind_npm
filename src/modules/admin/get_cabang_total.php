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

// Safe query function
function safe_query($sql) {
    global $conn;
    $result = $conn->query($sql);
    return $result;
}

try {
    $cabang_id = (int)($_GET['cabang_id'] ?? 0);
    $tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-d');
    $tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');

    // Validate input
    if ($cabang_id < 0) {
        echo json_encode(['success' => false, 'message' => 'Cabang ID tidak valid', 'total' => 0]);
        exit;
    }

    // Sanitize dates
    $tanggal_mulai = preg_replace('/[^\d\-]/', '', $tanggal_mulai);
    $tanggal_akhir = preg_replace('/[^\d\-]/', '', $tanggal_akhir);

    // Query for unsettled transactions
    if ($cabang_id == 0) {
        // All cabangs
        $query = "
            SELECT COALESCE(SUM(total_harga), 0) as total
            FROM transaksi_header
            WHERE DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
              AND (sudah_disetor = 0 OR sudah_disetor IS NULL)
        ";
    } else {
        // Specific cabang
        $query = "
            SELECT COALESCE(SUM(total_harga), 0) as total
            FROM transaksi_header
            WHERE cabang_id = $cabang_id
              AND DATE(created_at) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
              AND (sudah_disetor = 0 OR sudah_disetor IS NULL)
        ";
    }

    $result = safe_query($query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error, 'total' => 0]);
        exit;
    }

    $row = $result->fetch_assoc();
    $total = (float)($row['total'] ?? 0);

    echo json_encode([
        'success' => true,
        'total' => $total,
        'message' => 'Total berhasil diambil'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage(), 'total' => 0]);
}
