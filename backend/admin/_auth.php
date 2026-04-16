<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/../admin/index.php');
    exit;
}
