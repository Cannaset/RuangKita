<?php
// ============================================================
// RuangKita - Query Update Status
// File: App/admin/queries/update_status.php
// ============================================================

$statusAliases = [
    'pending' => 'not_reviewed',
    'approved' => 'resolved',
    'completed' => 'resolved',
    'in_progress' => 'in_process',
];

$newStatus = $statusAliases[$newStatus] ?? $newStatus;

if ($postId <= 0) {
    respondJson(400, [
        'success' => false,
        'message' => 'Data status tidak valid: post_id kosong atau tidak valid.',
    ]);
}

if (!array_key_exists($newStatus, $statusOptions)) {
    respondJson(400, [
        'success' => false,
        'message' => 'Data status tidak valid: status "' . $newStatus . '" tidak dikenali.',
        'valid_statuses' => array_keys($statusOptions),
    ]);
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

        // Trigger notifikasi ke student pembuat post
        $statusLabels = [
            'not_reviewed' => 'Belum Ditinjau',
            'in_process' => 'Sedang Diproses',
            'communicated' => 'Dikomunikasikan',
            'resolved' => 'Selesai',
            'rejected' => 'Ditolak',
        ];
        $statusLabel = $statusLabels[$newStatus] ?? $newStatus;
        $notifMsg = "Status aspirasi kamu telah diubah menjadi \"$statusLabel\".";

        $stmtOwner = $conn->prepare('SELECT student_id FROM posts WHERE id = ? LIMIT 1');
        $stmtOwner->bind_param('i', $postId);
        $stmtOwner->execute();
        $owner = $stmtOwner->get_result()->fetch_assoc();

        if ($owner) {
            $stmtNotif = $conn->prepare('
        INSERT INTO notifications (student_id, post_id, type, message)
        VALUES (?, ?, "status_change", ?)
    ');
            $stmtNotif->bind_param('iis', $owner['student_id'], $postId, $notifMsg);
            $stmtNotif->execute();
        }
    }

    $conn->commit();
    $meta = statusMeta($newStatus);

    respondJson(200, [
        'success' => true,
        'message' => 'Status aspirasi berhasil diperbarui.',
        'post_id' => $postId,
        'old_status' => $oldStatus,
        'status' => $newStatus,
        'status_label' => $meta['label'],
        'status_class' => $meta['class'],
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
} catch (Throwable $error) {
    $conn->rollback();
    respondJson(500, ['success' => false, 'message' => 'Gagal memperbarui status.']);
}
