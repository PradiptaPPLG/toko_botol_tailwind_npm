<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_login()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get all stok_cabang data
$stok_data = query("SELECT produk_id, cabang_id, stok FROM stok_cabang");

// Transform to key-value format: "produk_id_cabang_id" => stok
$result = [];
foreach ($stok_data as $row) {
    $key = $row['produk_id'] . '_' . $row['cabang_id'];
    $result[$key] = intval($row['stok']);
}

echo json_encode($result);
