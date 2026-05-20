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
