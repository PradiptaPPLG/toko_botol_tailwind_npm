<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!is_login()) redirect('../../login.php');

// Redirect to penjual as default
header("Location: penjual.php");
exit;
    
