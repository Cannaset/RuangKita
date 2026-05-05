<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['student'])) {
    header('Location: feed.php');
    exit;
}

$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $nim = trim($_POST['nim'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirmPassword'] ?? '');

    if ($username === '' || $nim === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $message = 'Semua kolom wajib diisi';
    } elseif (strlen($nim) < 8) {
        $message = 'NIM harus minimal 8 digit';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Format email belum valid';
    } elseif (strlen($password) < 6) {
        $message = 'Password minimal 6 karakter';
    } elseif ($password !== $confirmPassword) {
        $message = 'Konfirmasi password tidak sama';
    } else {
        $stmt = mysqli_prepare($conn, 
            "SELECT id FROM students WHERE nim = ? OR email = ? LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "ss", $nim, $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $student = mysqli_fetch_assoc($result);

        $stmt = mysqli_prepare($conn, "SELECT id FROM students WHERE nim = ? OR email = ? LIMIT 1");

        mysqli_stmt_bind_param($stmt, "ss", $nim, $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $existing = mysqli_fetch_assoc($result);

        if ($existing) {
            $message = 'NIM atau email sudah terdaftar';
        } else {

            // INSERT DATA
            $stmt = mysqli_prepare($conn, 
                "INSERT INTO students (username, nim, email, password, profile_picture) 
                VALUES (?, ?, ?, ?, ?)"
            );

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $profile_picture = null;

            mysqli_stmt_bind_param($stmt, "sssss", 
                $username, 
                $nim, 
                $email, 
                $hashedPassword, 
                $profile_picture
            );

            mysqli_stmt_execute($stmt);

            $_SESSION['flash_message'] = 'Akun berhasil dibuat. Silahkan login.';
            header('Location: index.php');
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
    <title>RuangKita - Signup</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link rel="stylesheet" href="../CSS/style-login.css?v=20260504-2">
</head>

<body class="auth-page">
    <button class="theme-toggle" id="themeToggle" type="button" aria-label="Ganti tema">
        <span id="themeIcon">Dark</span>
    </button>

    <main class="login-box auth-card signup-card">
        <div class="logo" aria-label="Logo RuangKita"></div>

        <p class="tagline">
            Daftar akun mahasiswa untuk mulai menyampaikan aspirasi.
        </p>

        <form id="signupForm" method="POST" action="signup.php" novalidate>
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Masukkan username" autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="input-group">
                <label for="nim">NIM</label>
                <input type="text" id="nim" name="nim" placeholder="Masukkan NIM" autocomplete="username" value="<?= htmlspecialchars($_POST['nim'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="emailkamu@gmail.com" autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" autocomplete="new-password">
            </div>

            <div class="input-group">
                <label for="confirmPassword">Konfirmasi Password</label>
                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Ulangi password" autocomplete="new-password">
            </div>

            <p class="message <?= $messageType; ?>" id="signup-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>

            <button type="submit">Daftar</button>
        </form>

        <p class="auth-switch">
            Sudah punya akun? <a href="index.php">Login di sini</a>
        </p>

        <a href="feed.php" class="skip-link">Lanjut ke Feed -></a>
    </main>

    <script src="../JS/script-signup.js?v=20260504-2"></script>
</body>

</html>
