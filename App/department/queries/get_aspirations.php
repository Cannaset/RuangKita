<?php

$where = "WHERE p.status IN ('in_process', 'communicated', 'resolved')";
$params = [];
$types = '';

if ($search !== '') {
    $where .= ' AND (p.title LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if ($category !== '') {
    $where .= ' AND p.category = ?';
    $params[] = $category;
    $types .= 's';
}

if ($status !== '') {
    $where .= ' AND p.status = ?';
    $params[] = $status;
    $types .= 's';
}

$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM posts p $where");
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalPosts = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$totalPages = max(1, (int) ceil($totalPosts / $perPage));

$sql = "
    SELECT
        p.id,
        p.title,
        p.description,
        p.category,
        p.status,
        p.created_at,
        p.updated_at,
        p.image_url,
        p.is_anonymous,
        s.nim,
        s.email,
        CASE
            WHEN p.is_anonymous = 1 THEN 'Anonim'
            ELSE COALESCE(s.username, 'Mahasiswa')
        END AS author,
        COALESCE(vt.upvotes, 0) AS upvotes,
        COALESCE(vt.downvotes, 0) AS downvotes,
        COALESCE(ct.comments_count, 0) AS comments_count
    FROM posts p
    LEFT JOIN students s ON s.id = p.student_id
    LEFT JOIN (
        SELECT
            post_id,
            SUM(vote_type = 'upvote') AS upvotes,
            SUM(vote_type = 'downvote') AS downvotes
        FROM votes
        GROUP BY post_id
    ) vt ON vt.post_id = p.id
    LEFT JOIN (
        SELECT post_id, COUNT(*) AS comments_count
        FROM comments
        GROUP BY post_id
    ) ct ON ct.post_id = p.id
    LEFT JOIN post_status_logs latest_log ON latest_log.id = (
        SELECT psl.id
        FROM post_status_logs psl
        WHERE psl.post_id = p.id
        ORDER BY psl.changed_at DESC, psl.id DESC
        LIMIT 1
    )
    $where
    ORDER BY COALESCE(latest_log.changed_at, p.updated_at, p.created_at) DESC
    LIMIT $perPage OFFSET $offset
";

$listStmt = $conn->prepare($sql);
if ($types !== '') {
    $listStmt->bind_param($types, ...$params);
}
$listStmt->execute();
$posts = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$responsesByPost = [];
if ($posts) {
    $postIds = array_map(static fn(array $post): int => (int) $post['id'], $posts);
    $postIdList = implode(',', $postIds);
    $responseResult = $conn->query("
        SELECT
            ar.post_id,
            ar.response,
            ar.created_at,
            COALESCE(ad.username, 'Admin') AS admin_name
        FROM admin_responses ar
        LEFT JOIN admins ad ON ad.id = ar.admin_id
        WHERE ar.post_id IN ($postIdList)
        ORDER BY ar.created_at ASC
    ");

    if ($responseResult) {
        while ($response = $responseResult->fetch_assoc()) {
            $responsesByPost[(int) $response['post_id']][] = $response;
        }
    }
}
