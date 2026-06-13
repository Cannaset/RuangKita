<?php
// ============================================================
// RuangKita - Status Update API
// File: App/api/status.php
//
// Endpoint yang tersedia:
//   PATCH /api/status.php   → Update status post + catat ke log
//   GET   /api/status.php?post_id= → Lihat riwayat status suatu post
//
// Yang boleh update status: admin atau department (BUKAN student biasa)
// ============================================================

session_start();
require_once __DIR__ . '/../config/database.php';

// --- Helper: kirim response JSON dan stop ---
function respond(int $code, array $data): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// --- Cek siapa yang login: admin atau department ---
// Kita cek $_SESSION['admin'] atau $_SESSION['department']
$admin      = $_SESSION['admin']      ?? null;
$department = $_SESSION['department'] ?? null;

// Kalau tidak ada keduanya → tolak
if (!$admin && !$department) {
    respond(401, ['success' => false, 'message' => 'Hanya admin atau department yang bisa mengubah status.']);
}

// Tentukan role dan id pengubah
if ($admin) {
    $changer_role = 'admin';
    $changer_id   = (int) $admin['id'];
} else {
    $changer_role = 'department';
    $changer_id   = (int) $department['id'];
}

$method = $_SERVER['REQUEST_METHOD'];

// Status yang valid
// $valid_statuses = ['not_reviewed', 'in_process', 'communicated', 'resolved', 'rejected'];
$valid_statuses = [
    'not_reviewed',
    'in_process',
    'communicated',
    'resolved',
    'rejected'
];
$department_statuses = [
    'in_process',
    'communicated',
    'resolved'
];

// ============================================================
// GET - Lihat riwayat status suatu post
// Contoh: GET /api/status.php?post_id=5
// ============================================================
if ($method === 'GET') {
    $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;

    if ($post_id <= 0) {
        respond(400, ['success' => false, 'message' => 'post_id tidak valid.']);
    }

    // Ambil status sekarang dari tabel posts
    $stmt = $conn->prepare("SELECT id, title, status FROM posts WHERE id = ?");
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();

    if (!$post) {
        respond(404, ['success' => false, 'message' => 'Post tidak ditemukan.']);
    }

    if ($changer_role === 'department' && in_array($post['status'], ['not_reviewed', 'rejected'], true)) {
        respond(403, [
            'success' => false,
            'message' => 'Department hanya dapat melihat riwayat postingan yang sudah disetujui admin.'
        ]);
    }

    // Ambil semua riwayat perubahan status dari tabel post_status_logs
    $stmt = $conn->prepare("
        SELECT
            psl.id,
            psl.changed_by_role,
            psl.changed_by_id,
            psl.old_status,
            psl.new_status,
            psl.note,
            psl.changed_at
        FROM post_status_logs psl
        WHERE psl.post_id = ?
        ORDER BY psl.changed_at ASC
    ");
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    respond(200, [
        'success'        => true,
        'post_id'        => $post_id,
        'post_title'     => $post['title'],
        'current_status' => $post['status'],
        'history'        => $logs,
    ]);
}

// ============================================================
// PATCH - Update status post
// Body JSON: { "post_id": 5, "new_status": "in_process", "note": "Sedang ditangani" }
//
// Apa yang terjadi di sini:
//   1. Ambil status lama dari tabel posts
//   2. Update status di tabel posts
//   3. Catat perubahan ke tabel post_status_logs
// ============================================================
if ($method === 'PATCH') {
    $body       = json_decode(file_get_contents('php://input'), true);
    $post_id    = isset($body['post_id'])    ? (int)   $body['post_id']    : 0;
    $new_status = isset($body['new_status']) ? trim($body['new_status'])    : '';
    $note       = isset($body['note'])       ? trim($body['note'])          : null;

    // Validasi input
    if ($post_id <= 0) {
        respond(400, ['success' => false, 'message' => 'post_id tidak valid.']);
    }
    if (!in_array($new_status, $valid_statuses, true)) {
        respond(400, [
            'success' => false,
            'message' => 'new_status tidak valid. Pilihan: not_reviewed, in_process, communicated, resolved, rejected.'
        ]);
    }
    if ($changer_role === 'department' && !in_array($new_status, $department_statuses, true)) {
        respond(403, [
            'success' => false,
            'message' => 'Department hanya dapat mengubah status menjadi in_process, communicated, atau resolved.'
        ]);
    }

    // Ambil status lama dari post
    $stmt = $conn->prepare("SELECT id, title, status FROM posts WHERE id = ?");
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();

    if (!$post) {
        respond(404, ['success' => false, 'message' => 'Post tidak ditemukan.']);
    }

    $old_status = $post['status'];

    if ($changer_role === 'department' && in_array($old_status, ['not_reviewed', 'rejected'], true)) {
        respond(403, [
            'success' => false,
            'message' => 'Postingan ini belum disetujui admin atau sudah ditolak.'
        ]);
    }

    // Kalau statusnya sama, tidak perlu diubah
    if ($old_status === $new_status) {
        respond(400, [
            'success' => false,
            'message' => "Status post sudah '$new_status', tidak ada perubahan.",
        ]);
    }

    // --- Mulai transaksi supaya kedua query berhasil atau gagal bersama ---
    $conn->begin_transaction();

    try {
        // 1. Update status di tabel posts
        $stmt = $conn->prepare("UPDATE posts SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $new_status, $post_id);
        $stmt->execute();

        // 2. Catat ke post_status_logs
        $stmt = $conn->prepare("
            INSERT INTO post_status_logs
                (post_id, changed_by_role, changed_by_id, old_status, new_status, note)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'isisss',
            $post_id,
            $changer_role,
            $changer_id,
            $old_status,
            $new_status,
            $note
        );
        $stmt->execute();

        $conn->commit();

        respond(200, [
            'success'    => true,
            'message'    => 'Status berhasil diperbarui.',
            'post_id'    => $post_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'note'       => $note,
        ]);

    } catch (Exception $e) {
        // Kalau ada error, batalkan semua perubahan
        $conn->rollback();
        respond(500, ['success' => false, 'message' => 'Gagal update status: ' . $e->getMessage()]);
    }
}

// Kalau method selain GET/PATCH
respond(405, ['success' => false, 'message' => 'Method tidak diizinkan.']);
