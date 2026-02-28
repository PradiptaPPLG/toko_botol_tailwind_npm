<?php
// includes/layout_header.php;
require_once 'config.php';
$root_path = $root ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <title><?= $title ?? 'Aplikasi Kasir Botol' ?></title>

    <!-- Tailwind NPM BUILD (PRODUCTION) -->
     <link href="<?= $root_path ?>dist/tailwind.css" rel="stylesheet"> <!-- href nya harus diubah jadi relative path -->
</head>

<body class="font-sans antialiased bg-gray-100">
<!-- AWAL BODY - TIDAK ADA DIV FLEX DISINI -->
<!-- Modal confirmation is loaded conditionally via includes/modal_confirm.php on pages that need it -->
