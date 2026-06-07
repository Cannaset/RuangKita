<?php
// ============================================================
// RuangKita - Query Get All Aspirations
// File: App/admin/queries/get_all_aspirations.php
// ============================================================

$where = [];
$types = '';
$params = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = '(p.title LIKE ? OR p.description LIKE ? OR s.username LIKE ?)';
    $types .= 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($categoryFilter !== '') {
    $where[] = 'p.category = ?';
    $types .= 's';
    $params[] = $categoryFilter;
}

$statusValues = normalizeStatusFilter($statusFilter);
if (count($statusValues) === 1) {
    $where[] = 'p.status = ?';
    $types .= 's';
    $params[] = $statusValues[0];
} elseif (count($statusValues) > 1) {
    $placeholders = implode(',', array_fill(0, count($statusValues), '?'));
    $where[] = "p.status IN ($placeholders)";
    $types .= str_repeat('s', count($statusValues));
    foreach ($statusValues as $statusValue) {
        $params[] = $statusValue;
    }
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$orderSql = match ($sort) {
    'oldest' => 'p.created_at ASC',
    'votes' => 'COALESCE(vt.upvotes, 0) DESC, p.created_at DESC',
    default => 'p.created_at DESC',
};

$countSql = "
    SELECT COUNT(*) AS total
    FROM posts p
    LEFT JOIN students s ON s.id = p.student_id
    $whereSql
";
$countStmt = $conn->prepare($countSql);
$countParams = $params;
bindParams($countStmt, $types, $countParams);
$countStmt->execute();
$totalRows = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$listSql = "
    SELECT
        p.id,
        p.student_id,
        p.title,
        p.description,
        p.category,
        p.image_url,
        p.is_anonymous,
        p.status,
        p.created_at,
        p.updated_at,
        s.username,
        s.nim,
        s.email,
        COALESCE(vt.upvotes, 0) AS upvotes,
        COALESCE(vt.downvotes, 0) AS downvotes,
        COALESCE(ct.comments_count, 0) AS comments_count,
        COALESCE(rt.response_count, 0) AS response_count,
        lr.response AS latest_response,
        lr.created_at AS latest_response_at
    FROM posts p
    LEFT JOIN students s ON s.id = p.student_id
    LEFT JOIN (
        SELECT post_id,
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
    LEFT JOIN (
        SELECT post_id, COUNT(*) AS response_count
        FROM admin_responses
        GROUP BY post_id
    ) rt ON rt.post_id = p.id
    LEFT JOIN admin_responses lr ON lr.id = (
        SELECT ar.id
        FROM admin_responses ar
        WHERE ar.post_id = p.id
        ORDER BY ar.created_at DESC
        LIMIT 1
    )
    $whereSql
    ORDER BY $orderSql
    LIMIT ? OFFSET ?
";
$listStmt = $conn->prepare($listSql);
$listParams = $params;
$listTypes = $types . 'ii';
$listParams[] = $perPage;
$listParams[] = $offset;
bindParams($listStmt, $listTypes, $listParams);
$listStmt->execute();
$posts = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$responsesByPost = [];
if ($posts) {
    $postIds = array_map(fn($post) => (int) $post['id'], $posts);
    $placeholders = implode(',', array_fill(0, count($postIds), '?'));
    $responseTypes = str_repeat('i', count($postIds));
    $responseSql = "
    SELECT ar.id, ar.post_id, ar.response, ar.created_at, COALESCE(ad.username, 'Admin') AS admin_name
    FROM admin_responses ar
    LEFT JOIN admins ad ON ad.id = ar.admin_id
    WHERE ar.post_id IN ($placeholders)
    ORDER BY ar.created_at ASC
";

    $responseStmt = $conn->prepare($responseSql);
    $responseParams = $postIds;
    bindParams($responseStmt, $responseTypes, $responseParams);
    $responseStmt->execute();
    $responseRows = $responseStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($responseRows as $responseRow) {
        $responsesByPost[(int) $responseRow['post_id']][] = $responseRow;
    }
}
