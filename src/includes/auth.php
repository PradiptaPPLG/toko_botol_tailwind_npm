<?php

use JetBrains\PhpStorm\NoReturn;

require_once 'database.php';

function is_login(): bool
{
    return isset($_SESSION['user']);
}

function is_admin(): bool
{
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

#[NoReturn]
function redirect($url): void
{
    header("Location: $url");
    exit;
}

function login_admin($username, $password): bool
{
    $username = escape_string($username);
    $result = query("SELECT * FROM admin WHERE username = '$username'");
    
    if (count($result) > 0) {
        $admin = $result[0];
        if (password_verify($password, $admin['password'])) {
            $_SESSION['user'] = [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'nama' => $admin['nama_lengkap'],
                'role' => 'admin'
            ];
            return true;
        }
    }
    return false;
}

function login_kasir($nama, $cabang_id): true
{
    $nama = escape_string($nama);
    $cabang_id = intval($cabang_id);
    
    $sql = "INSERT INTO session_kasir (nama_kasir, cabang_id) VALUES ('$nama', $cabang_id)";
    execute($sql);
    $session_id = last_insert_id();
    
    // Simpan ke riwayat
    execute("INSERT INTO riwayat_login_kasir (nama_kasir, cabang_id) VALUES ('$nama', $cabang_id)");
    
    $_SESSION['user'] = [
        'id' => $session_id,
        'nama' => $nama,
        'cabang_id' => $cabang_id,
        'role' => 'kasir'
    ];
    return true;
}

#[NoReturn]
function logout(): void
{
    if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'kasir') {
        $id = $_SESSION['user']['id'];
        execute("UPDATE session_kasir SET logout_time = NOW(), status = 'logout' WHERE id = $id");
    }
    session_destroy();
    redirect('login.php');
}
