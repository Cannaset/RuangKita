<?php
// ============================================================
// RuangKita – API: Update Profile
// App/api/update-profile.php
// Handles: update_profile | change_password | update_avatar
// Supports: student | admin | department
// ============================================================

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

function jsonOut(bool $success, string $message, array $extra = []): void
{
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

function sanitize(string $v): string
{
    return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8'));
}

// ── Auth check ────────────────────────────────────────────
$student = $_SESSION['student'] ?? null;
$admin = $_SESSION['admin'] ?? null;
$department = $_SESSION['department'] ?? null;

if (!$student && !$admin && !$department) {
    jsonOut(false, 'Tidak terautentikasi.');
}

$action = $_POST['action'] ?? '';
if ($student) {
    $userId = $student['id'];
    $userType = 'student';
    $table = 'students';
} elseif ($admin) {
    $userId = $admin['id'];
    $userType = 'admin';
    $table = 'admins';
} elseif ($department) {
    $userId = $department['id'];
    $userType = 'department';
    $table = 'departments';
} else {
    jsonOut(false, 'Tidak terautentikasi.');
}

// ============================================================
// ACTION: update_profile
// ============================================================
if ($action === 'update_profile') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');

    if (strlen($username) < 3)
        jsonOut(false, 'Username minimal 3 karakter.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        jsonOut(false, 'Format email tidak valid.');

    // Check email uniqueness (exclude current user)
    $stmt = $conn->prepare("SELECT id FROM {$table} WHERE email = ? AND id != ? LIMIT 1");
    $stmt->bind_param('si', $email, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        jsonOut(false, 'Email sudah digunakan akun lain.');
    }

    if ($userType === 'department') {
        $deptName = sanitize($_POST['dept_name'] ?? '');
        $stmt = $conn->prepare("UPDATE {$table} SET username=?, email=?, name=? WHERE id=?");
        $stmt->bind_param('sssi', $username, $email, $deptName, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE {$table} SET username=?, email=? WHERE id=?");
        $stmt->bind_param('ssi', $username, $email, $userId);
    }

    if (!$stmt->execute())
        jsonOut(false, 'Gagal memperbarui profil: ' . $conn->error);

    // Refresh session
    if ($userType === 'student')
        $_SESSION['student']['username'] = $username;
    if ($userType === 'admin')
        $_SESSION['admin']['username'] = $username;
    if ($userType === 'department')
        $_SESSION['department']['username'] = $username;

    jsonOut(true, 'Profil berhasil diperbarui!');
}

// ============================================================
// ACTION: change_password
// ============================================================
if ($action === 'change_password') {
    $oldPw = $_POST['old_password'] ?? '';
    $newPw = $_POST['new_password'] ?? '';
    $confPw = $_POST['confirm_password'] ?? '';

    if (strlen($newPw) < 6)
        jsonOut(false, 'Password baru minimal 6 karakter.');
    if ($newPw !== $confPw)
        jsonOut(false, 'Konfirmasi password tidak cocok.');

    // Fetch current hash
    $stmt = $conn->prepare("SELECT password FROM {$table} WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row)
        jsonOut(false, 'User tidak ditemukan.');

    $stored = $row['password'];
    $valid = password_verify($oldPw, $stored) || hash_equals($stored, $oldPw);

    if (!$valid)
        jsonOut(false, 'Password lama tidak sesuai.');
    if ($oldPw === $newPw)
        jsonOut(false, 'Password baru tidak boleh sama dengan password lama.');

    $hash = password_hash($newPw, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE {$table} SET password=? WHERE id=?");
    $stmt->bind_param('si', $hash, $userId);

    if (!$stmt->execute())
        jsonOut(false, 'Gagal memperbarui password.');

    jsonOut(true, 'Password berhasil diubah!');
}

// ============================================================
// ACTION: update_avatar
// ============================================================
if ($action === 'update_avatar') {
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        jsonOut(false, 'File tidak valid atau tidak ditemukan.');
    }

    $file = $_FILES['avatar'];
    $mimeType = mime_content_type($file['tmp_name']);
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($mimeType, $allowed)) {
        jsonOut(false, 'Hanya file gambar (JPG, PNG, GIF, WEBP) yang diizinkan.');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        jsonOut(false, 'Ukuran file maksimal 5MB.');
    }

    // Determine upload dir (relative to App root)
    $uploadDir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $filename = $userType . '_' . $userId . '_' . time() . '.' . $ext;
    $dest = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonOut(false, 'Gagal menyimpan file. Cek izin folder uploads/.');
    }

    // Delete old avatar if exists
    $stmt = $conn->prepare("SELECT profile_picture FROM {$table} WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    if (!empty($old['profile_picture'])) {
        $oldPath = __DIR__ . '/../' . ltrim($old['profile_picture'], '/');
        if (file_exists($oldPath))
            @unlink($oldPath);
    }

    $relativePath = '../uploads/avatars/' . $filename;
    $stmt = $conn->prepare("UPDATE {$table} SET profile_picture=? WHERE id=?");
    $stmt->bind_param('si', $relativePath, $userId);

    if (!$stmt->execute()) {
        jsonOut(false, 'Gagal menyimpan path avatar ke database.');
    }

    // Refresh session
    $url = $relativePath;
    if ($userType === 'student')
        $_SESSION['student']['profile_picture'] = $relativePath;
    if ($userType === 'admin')
        $_SESSION['admin']['profile_picture'] = $relativePath;
    if ($userType === 'department')
        $_SESSION['department']['profile_picture'] = $relativePath;

    jsonOut(true, 'Foto profil berhasil diperbarui!', ['url' => $url]);
}

jsonOut(false, 'Action tidak dikenali.');
