<?php
// ============================================================
// RuangKita - Query Update Status
// File: App/admin/queries/update_status.php
// ============================================================

if ($postId <= 0 || !array_key_exists($newStatus, $statusOptions)) {
    respondJson(400, ['success' => false, 'message' => 'Data status tidak valid.']);
}

$stmt = $conn->prepare('SELECT id, status FROM posts WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $postId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    respondJson(404, ['success' => false, 'message' => 'Aspirasi tidak ditemukan.']);
}

$oldStatus = $post['status'];
$note = 'Status diperbarui melalui admin dashboard.';

$conn->begin_transaction();

try {
    if ($oldStatus !== $newStatus) {
        $stmt = $conn->prepare('UPDATE posts SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $newStatus, $postId);
        $stmt->execute();

        $role = 'admin';
        $stmt = $conn->prepare("
            INSERT INTO post_status_logs (post_id, changed_by_role, changed_by_id, old_status, new_status, note)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('isisss', $postId, $role, $adminId, $oldStatus, $newStatus, $note);
        $stmt->execute();
    }

    $conn->commit();
    $meta = statusMeta($newStatus);

    respondJson(200, [
        'success' => true,
        'message' => 'Status aspirasi berhasil diperbarui.',
        'post_id' => $postId,
        'status' => $newStatus,
        'status_label' => $meta['label'],
        'status_class' => $meta['class'],
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
} catch (Throwable $error) {
    $conn->rollback();
    respondJson(500, ['success' => false, 'message' => 'Gagal memperbarui status.']);
}
