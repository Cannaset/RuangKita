<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../auth/login-admin.php');
    exit;
}

$admin = $_SESSION['admin'];
