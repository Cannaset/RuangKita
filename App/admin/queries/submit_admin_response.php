<?php
// ============================================================
// RuangKita - Query Submit Admin Response
// File: App/admin/queries/submit_admin_response.php
// ============================================================

if ($postId <= 0 || $response === '') {
    respondJson(400, ['success' => false, 'message' => 'Tanggapan tidak boleh kosong.']);
}

$stmt = $conn->prepare('SELECT id FROM posts WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $postId);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    respondJson(404, ['success' => false, 'message' => 'Aspirasi tidak ditemukan.']);
}

$adminId = (int) $admin['id'];
$stmt = $conn->prepare('INSERT INTO admin_responses (post_id, admin_id, response) VALUES (?, ?, ?)');
$stmt->bind_param('iis', $postId, $adminId, $response);

if (!$stmt->execute()) {
    respondJson(500, ['success' => false, 'message' => 'Gagal menyimpan tanggapan.']);
}

// Trigger notifikasi ke student pembuat post
$stmtOwner = $conn->prepare('SELECT student_id FROM posts WHERE id = ? LIMIT 1');
$stmtOwner->bind_param('i', $postId);
$stmtOwner->execute();
$owner = $stmtOwner->get_result()->fetch_assoc();

if ($owner) {
    $notifMsg = "Aspirasi kamu mendapat tanggapan resmi dari admin.";
    $stmtNotif = $conn->prepare('
        INSERT INTO notifications (student_id, post_id, type, message)
        VALUES (?, ?, "admin_response", ?)
    ');
    $stmtNotif->bind_param('iis', $owner['student_id'], $postId, $notifMsg);
    $stmtNotif->execute();
}

respondJson(201, [
    'success' => true,
    'message' => 'Tanggapan resmi berhasil dikirim.',
    'response' => [
        'id' => $stmt->insert_id,
        'post_id' => $postId,
        'admin_name' => $admin['username'] ?? 'Admin',
        'response' => $response,
        'created_at' => date('Y-m-d H:i:s'),
    ],
]);
