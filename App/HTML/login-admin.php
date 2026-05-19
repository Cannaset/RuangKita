<?php
// ============================================================
// RuangKita - Login Admin
// File: App/HTML/login-admin.php
// ============================================================

session_start();
require_once __DIR__ . '/../config/database.php';

// Kalau sudah login sebagai admin, langsung ke dashboard
if (isset($_SESSION['admin'])) {
    header('Location: dashboard-admin.php');
    exit;
}

$message     = $_SESSION['flash_message'] ?? '';
$messageType = isset($_SESSION['flash_message']) ? 'success' : 'error';
unset($_SESSION['flash_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    $messageType = 'error';

    if ($email === '' && $password === '') {
        $message = 'Email dan Password wajib diisi.';
    } elseif ($email === '') {
        $message = 'Email tidak boleh kosong.';
    } elseif ($password === '') {
        $message = 'Password tidak boleh kosong.';
    } else {
        // Cari admin berdasarkan email
        $stmt = $conn->prepare("
            SELECT id, username, email, password, profile_picture
            FROM admins
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if (!$admin || !password_verify($password, $admin['password'])) {
            $message = 'Email atau Password salah.';
        } else {
            // Set session admin — inilah yang dibaca oleh status.php
            $_SESSION['admin'] = [
                'id'              => $admin['id'],
                'username'        => $admin['username'],
                'email'           => $admin['email'],
                'profile_picture' => $admin['profile_picture'],
            ];
            header('Location: dashboard-admin.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangKita - Login Admin</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link rel="stylesheet" href="../CSS/style-login.css?v=20260504-2">
</head>

<body class="auth-page">
    <button class="theme-toggle" id="themeToggle" type="button" aria-label="Ganti tema">
        <span id="themeIcon">Dark</span>
    </button>

    <main class="login-box auth-card">
        <div class="logo" aria-label="Logo RuangKita"></div>

        <p class="tagline">
            Portal Admin RuangKita.<br>
            Moderasi dan kelola aspirasi mahasiswa.
        </p>

        <form id="loginAdminForm" method="POST" action="login-admin.php" novalidate>
            <div class="input-group">
                <label for="email">Email Admin</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Masukkan email admin"
                    autocomplete="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Masukkan Password"
                    autocomplete="current-password"
                >
            </div>

            <p class="message <?= $messageType; ?>" id="error-message">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <button type="submit">Login sebagai Admin</button>
        </form>

        <p class="footer-text">Hanya untuk pengurus yang berwenang.</p>
    </main>

    <script src="../JS/script-login.js?v=20260504-2"></script>
</body>

</html>