<?php
require_once 'includes/auth.php';

if (is_login()) {
    if (is_admin()) {
        redirect('dashboard.php');
    } else {
        redirect('modules/kasir/index.php');
    }
} else {
    redirect('login.php');
}

