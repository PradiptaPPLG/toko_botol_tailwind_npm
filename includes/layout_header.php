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

    <!-- Custom CSS (JANGAN DIUBAH) -->
    <style>
        body {
            background-color: #f3f4f6;
            font-family: system-ui, -apple-system, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%);
            transition: transform 0.3s ease;
        }
        
        .btn-primary {
            background-color: #2563eb;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background-color: #1e40af;
            transform: scale(1.01);
        }
        
        .btn-danger {
            background-color: #dc2626;
            transition: all 0.2s;
        }
        
        .btn-danger:hover {
            background-color: #b91c1c;
            transform: scale(1.01);
        }
        
        .card {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 0.5rem;
        }
        @media (max-width: 1000px) {
            .judul {
                margin-left: 3rem !important;
            }
        }
        /* Mobile Menu */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                z-index: 50;
                height: 100vh;
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100%;
            }
            
            .mobile-menu-btn {
                display: block !important;
            }
        }
        
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 100;
            background: #1e3c72;
            color: white;
            padding: 0.75rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        /* Animasi Smooth */
        * {
            transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }

        /* Custom Confirmation Modal */
        #confirmModal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        #confirmModal.show {
            opacity: 1;
            visibility: visible;
        }
        #confirmModal .modal-content {
            transform: scale(0.7);
            transition: transform 0.3s ease;
        }
        #confirmModal.show .modal-content {
            transform: scale(1);
        }
    </style>
</head>

<body class="font-sans antialiased bg-gray-100">
<!-- AWAL BODY - TIDAK ADA DIV FLEX DISINI -->
<!-- Modal confirmation is loaded conditionally via includes/modal_confirm.php on pages that need it -->
