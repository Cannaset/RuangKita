<?php
session_start();

if (!isset($_SESSION['department'])) {
    header('Location: ../auth/login-department.php');
    exit;
}

$dept = $_SESSION['department'];
$deptId = (int) $dept['id'];
