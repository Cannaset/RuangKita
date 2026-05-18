<?php
// ============================================================
// RuangKita - Vote System API
// File: App/api/votes.php
//
// Endpoint yang tersedia:
//   POST   /api/votes.php          → Tambah vote (upvote/downvote)
//   DELETE /api/votes.php          → Hapus vote (batalkan vote)
//   GET    /api/votes.php?post_id= → Lihat jumlah vote suatu post
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

// --- Hanya mahasiswa yang login yang boleh vote ---
$student = $_SESSION['student'] ?? null;
if (!$student) {
    respond(401, ['success' => false, 'message' => 'Kamu harus login dulu.']);
}

$student_id = (int) $student['id'];
$method     = $_SERVER['REQUEST_METHOD'];

// ============================================================
// GET - Lihat jumlah upvote & downvote suatu post
// Contoh: GET /api/votes.php?post_id=5
// ============================================================
if ($method === 'GET') {
    $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;

    if ($post_id <= 0) {
        respond(400, ['success' => false, 'message' => 'post_id tidak valid.']);
    }

    // Hitung total upvote dan downvote
    $stmt = $conn->prepare("
        SELECT
            SUM(vote_type = 'upvote')   AS upvotes,
            SUM(vote_type = 'downvote') AS downvotes
        FROM votes
        WHERE post_id = ?
    ");
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    // Cek apakah user ini sudah vote di post ini
    $stmt2 = $conn->prepare("
        SELECT vote_type FROM votes
        WHERE post_id = ? AND student_id = ?
    ");
    $stmt2->bind_param('ii', $post_id, $student_id);
    $stmt2->execute();
    $my_vote = $stmt2->get_result()->fetch_assoc();

    respond(200, [
        'success'   => true,
        'post_id'   => $post_id,
        'upvotes'   => (int) ($row['upvotes'] ?? 0),
        'downvotes' => (int) ($row['downvotes'] ?? 0),
        'my_vote'   => $my_vote['vote_type'] ?? null, // null = belum vote
    ]);
}

// ============================================================
// POST - Tambah atau ganti vote
// Body JSON: { "post_id": 5, "vote_type": "upvote" }
//
// Logika:
//   - Belum pernah vote    → tambah vote baru
//   - Sudah vote, tipe sama → batalkan vote (toggle off)
//   - Sudah vote, tipe beda → ganti vote
// ============================================================
if ($method === 'POST') {
    $body      = json_decode(file_get_contents('php://input'), true);
    $post_id   = isset($body['post_id'])   ? (int) $body['post_id']        : 0;
    $vote_type = isset($body['vote_type']) ? trim($body['vote_type'])       : '';

    // Validasi input
    if ($post_id <= 0) {
        respond(400, ['success' => false, 'message' => 'post_id tidak valid.']);
    }
    if (!in_array($vote_type, ['upvote', 'downvote'])) {
        respond(400, ['success' => false, 'message' => 'vote_type harus "upvote" atau "downvote".']);
    }

    // Pastikan post-nya ada
    $stmt = $conn->prepare("SELECT id FROM posts WHERE id = ?");
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        respond(404, ['success' => false, 'message' => 'Post tidak ditemukan.']);
    }

    // Cek apakah user sudah pernah vote di post ini
    $stmt = $conn->prepare("
        SELECT id, vote_type FROM votes
        WHERE post_id = ? AND student_id = ?
    ");
    $stmt->bind_param('ii', $post_id, $student_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if (!$existing) {
        // Belum pernah vote → INSERT baru
        $stmt = $conn->prepare("
            INSERT INTO votes (post_id, student_id, vote_type)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('iis', $post_id, $student_id, $vote_type);
        $stmt->execute();

        respond(201, [
            'success'  => true,
            'message'  => 'Vote berhasil ditambahkan.',
            'action'   => 'added',
            'vote_type' => $vote_type,
        ]);

    } elseif ($existing['vote_type'] === $vote_type) {
        // Vote sama → batalkan (toggle off), DELETE
        $stmt = $conn->prepare("
            DELETE FROM votes
            WHERE post_id = ? AND student_id = ?
        ");
        $stmt->bind_param('ii', $post_id, $student_id);
        $stmt->execute();

        respond(200, [
            'success'  => true,
            'message'  => 'Vote dibatalkan.',
            'action'   => 'removed',
            'vote_type' => null,
        ]);

    } else {
        // Vote beda → ganti (UPDATE)
        $stmt = $conn->prepare("
            UPDATE votes SET vote_type = ?
            WHERE post_id = ? AND student_id = ?
        ");
        $stmt->bind_param('sii', $vote_type, $post_id, $student_id);
        $stmt->execute();

        respond(200, [
            'success'  => true,
            'message'  => 'Vote diubah.',
            'action'   => 'changed',
            'vote_type' => $vote_type,
        ]);
    }
}

// ============================================================
// DELETE - Hapus vote secara eksplisit
// Body JSON: { "post_id": 5 }
// ============================================================
if ($method === 'DELETE') {
    $body    = json_decode(file_get_contents('php://input'), true);
    $post_id = isset($body['post_id']) ? (int) $body['post_id'] : 0;

    if ($post_id <= 0) {
        respond(400, ['success' => false, 'message' => 'post_id tidak valid.']);
    }

    $stmt = $conn->prepare("
        DELETE FROM votes
        WHERE post_id = ? AND student_id = ?
    ");
    $stmt->bind_param('ii', $post_id, $student_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        respond(404, ['success' => false, 'message' => 'Vote tidak ditemukan.']);
    }

    respond(200, ['success' => true, 'message' => 'Vote berhasil dihapus.']);
}

// Kalau method selain GET/POST/DELETE
respond(405, ['success' => false, 'message' => 'Method tidak diizinkan.']);