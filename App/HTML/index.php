<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['student'])) {
    header('Location: feed.php');
    exit;
}

$message = $_SESSION['flash_message'] ?? '';
$messageType = isset($_SESSION['flash_message']) ? 'success' : 'error';
unset($_SESSION['flash_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = trim($_POST['nim'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $messageType = 'error';

    if ($nim === '' && $password === '') {
        $message = 'NIM dan Password wajib diisi';
    } elseif ($nim === '') {
        $message = 'NIM tidak boleh kosong';
    } elseif ($password === '') {
        $message = 'Password tidak boleh kosong';
    } else {
        $stmt = mysqli_prepare($conn, 
    "SELECT id, username, nim, email, password, profile_picture 
        FROM students 
        WHERE nim = ? 
        LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "s", $nim);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $student = mysqli_fetch_assoc($result);

        if (!$student || !password_verify($password, $student['password'])) {
            $message = 'NIM atau Password salah';
        } else {
            $_SESSION['student'] = [
                'id' => $student['id'],
                'username' => $student['username'],
                'nim' => $student['nim'],
                'email' => $student['email'],
                'profile_picture' => $student['profile_picture'],
            ];
            header('Location: feed.php');
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
    <title>RuangKita - Login</title>
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
            Wadah aspirasi digital mahasiswa.<br>
            Suara didengar, perubahan dimulai.
        </p>

        <form id="loginForm" method="POST" action="index.php" novalidate>
            <div class="input-group">
                <label for="nim">NIM</label>
                <input type="text" id="nim" name="nim" placeholder="Masukkan NIM" autocomplete="username" value="<?= htmlspecialchars($_POST['nim'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan Password" autocomplete="current-password">
            </div>

            <p class="message <?= $messageType; ?>" id="error-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>

            <button type="submit">Login</button>
        </form>

        <p class="auth-switch">
            Belum punya akun? <a href="signup.php">Silahkan mendaftar</a>
        </p>

        <p class="footer-text">Ruang bersama untuk perubahan kampus.</p>

        <a href="feed.php" class="skip-link">Lanjut ke Feed -></a>
    </main>

    <script src="../JS/script-login.js?v=20260504-2"></script>
</body>

</html>
