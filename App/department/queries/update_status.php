<?php

header('Content-Type: application/json');

$postId = (int) ($_POST['post_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');
$note = trim($_POST['note'] ?? '');
$allowedStatuses = ['in_process', 'communicated', 'resolved'];

if ($postId <= 0 || !in_array($newStatus, $allowedStatuses, true)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid.']);
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare('SELECT status FROM posts WHERE id = ? LIMIT 1 FOR UPDATE');
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();

    if (!$post) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Postingan tidak ditemukan.']);
        exit;
    }

    $oldStatus = $post['status'];
    if (in_array($oldStatus, ['not_reviewed', 'rejected'], true)) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Postingan ini belum disetujui admin atau sudah ditolak.',
        ]);
        exit;
    }

    if ($oldStatus === $newStatus) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Status postingan tidak berubah.']);
        exit;
    }

    $stmt = $conn->prepare('UPDATE posts SET status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->bind_param('si', $newStatus, $postId);
    $stmt->execute();

    $stmt = $conn->prepare("
        INSERT INTO post_status_logs
            (post_id, changed_by_role, changed_by_id, old_status, new_status, note)
        VALUES (?, 'department', ?, ?, ?, ?)
    ");
    $stmt->bind_param('iisss', $postId, $deptId, $oldStatus, $newStatus, $note);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui.']);
    exit;
} catch (Throwable $error) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status.']);
    exit;
}
