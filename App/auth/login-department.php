<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['department'])) {
    header('Location: ../admin/dashboard.php');
    exit;
}

$message     = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' && $password === '') {
        $message = 'Email dan Password wajib diisi.';
    } elseif ($email === '') {
        $message = 'Email tidak boleh kosong.';
    } elseif ($password === '') {
        $message = 'Password tidak boleh kosong.';
    } else {
        $stmt = $conn->prepare("
            SELECT id, name, username, email, password, profile_picture
            FROM departments
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $dept = $stmt->get_result()->fetch_assoc();

        if (!$dept || !password_verify($password, $dept['password'])) {
            $message = 'Email atau Password salah.';
        } else {
            $_SESSION['department'] = [
                'id'              => $dept['id'],
                'name'            => $dept['name'],
                'username'        => $dept['username'],
                'email'           => $dept['email'],
                'profile_picture' => $dept['profile_picture'],
            ];
            header('Location: ../admin/dashboard.php');
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
    <title>RuangKita - Login Department</title>
    <link rel="stylesheet" href="../assets/CSS/style.css">
    <link rel="stylesheet" href="../assets/CSS/style-login.css?v=20260504-2">
</head>
<body class="auth-page">
    <button class="theme-toggle" id="themeToggle" type="button" aria-label="Ganti tema">
        <span id="themeIcon">Dark</span>
    </button>

    <main class="login-box auth-card">
        <div class="logo" aria-label="Logo RuangKita"></div>

        <p class="tagline">
            Portal Departemen RuangKita.<br>
            Tinjau dan perbarui status aspirasi mahasiswa.
        </p>

        <form id="loginDeptForm" method="POST" action="login-department.php" novalidate>
            <div class="input-group">
                <label for="email">Email Departemen</label>
                <input type="email" id="email" name="email"
                    placeholder="Masukkan email departemen"
                    autocomplete="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                    placeholder="Masukkan Password"
                    autocomplete="current-password">
            </div>

            <p class="message <?= $messageType; ?>" id="error-message">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <button type="submit">Login sebagai Departemen</button>
        </form>

        <p class="footer-text">Hanya untuk perwakilan departemen yang berwenang.</p>
        <p class="auth-switch"><a href="index.php">← Kembali</a></p>
    </main>

    <script src="../assets/JS/script-login.js?v=20260504-2"></script>
</body>
</html>
