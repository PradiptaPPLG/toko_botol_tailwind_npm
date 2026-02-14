<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'kasir_toko';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$base_url = 'http://localhost/kasir_toko/';
?>