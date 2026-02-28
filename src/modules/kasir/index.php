<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!is_login()) redirect('../../login.php');

// Redirect to pembeli as default
header("Location: pembeli.php");
exit;
