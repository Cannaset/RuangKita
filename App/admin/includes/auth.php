<?php
session_start();

// Dashboard bisa diakses oleh admin ATAU department
$isAdmin      = isset($_SESSION['admin']);
$isDepartment = isset($_SESSION['department']);

if (!$isAdmin && !$isDepartment) {
    header('Location: ../auth/login-admin.php');
    exit;
}

// Tentukan siapa yang sedang login, simpan ke $currentUser
if ($isAdmin) {
    $admin       = $_SESSION['admin'];
    $currentUser = $admin;
    $currentRole = 'admin';
} else {
    $currentUser = $_SESSION['department'];
    $currentRole = 'department';
}
