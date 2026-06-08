<?php
// ============================================================
// RuangKita - API Notifikasi
// File: App/api/notifications.php
//
// GET  → ambil notifikasi milik student yang sedang login
// POST {action:"mark_read", id?:X} → tandai sudah dibaca
// ============================================================
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$student = $_SESSION['student'] ?? null;
if (!$student) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Kamu harus login dulu.']);
    exit;
}

$student_id = (int) $student['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->prepare('
    SELECT id, post_id, type, message, is_read, created_at
    FROM notifications
    WHERE student_id = ?
    ORDER BY created_at DESC
    LIMIT 20
');
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($rows as &$row) {
        $row['id'] = (int) $row['id'];
        $row['post_id'] = (int) $row['post_id'];
        $row['is_read'] = (bool) $row['is_read'];
    }

    $unread = count(array_filter($rows, fn($r) => !$r['is_read']));
    echo json_encode(['success' => true, 'unread' => $unread, 'data' => $rows]);
    exit;
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';

    if ($action === 'mark_read') {
        $notif_id = isset($body['id']) ? (int) $body['id'] : 0;
        if ($notif_id > 0) {
            $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND student_id = ?');
            $stmt->bind_param('ii', $notif_id, $student_id);
        } else {
            $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE student_id = ?');
            $stmt->bind_param('i', $student_id);
        }
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
