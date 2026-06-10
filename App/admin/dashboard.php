<?php
// ============================================================
// RuangKita - Admin Dashboard Controller
// File: App/admin/dashboard.php
// ============================================================

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/helper.php';

// Ensure the required database schemas and tables exist
ensureAdminSchema($conn);

$statusOptions = [
    'not_reviewed' => 'Pending',
    'in_process' => 'In Progress',
    'communicated' => 'Communicated',
    'resolved' => 'Completed',
    'rejected' => 'Rejected',
];

// Handle AJAX forms / POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only handle JSON / XHR requests here; ignore browser form reloads
    $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    if (!$isAjax) {
        respondJson(400, ['success' => false, 'message' => 'Permintaan tidak valid.']);
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $postId = (int) ($_POST['post_id'] ?? $_POST['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $adminId = (int) $admin['id'];
        require __DIR__ . '/queries/update_status.php';
        exit; // update_status.php calls respondJson+exit, but be explicit
    }

    if ($action === 'add_response') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        $response = trim($_POST['response'] ?? '');
        require __DIR__ . '/queries/submit_admin_response.php';
        exit;
    }

    if ($action === 'delete_post') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        if ($postId <= 0) {
            respondJson(400, ['success' => false, 'message' => 'post_id tidak valid.']);
        }

        $stmt = $conn->prepare('SELECT image_url FROM posts WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            respondJson(404, ['success' => false, 'message' => 'Post tidak ditemukan.']);
        }

        if (!empty($row['image_url'])) {
            $imageUrl = str_replace('../', '', $row['image_url']);
            $filePath = __DIR__ . '/../../' . $imageUrl;
            if (file_exists($filePath))
                unlink($filePath);
        }

        $del = $conn->prepare('DELETE FROM posts WHERE id = ?');
        $del->bind_param('i', $postId);

        if (!$del->execute()) {
            respondJson(500, ['success' => false, 'message' => 'Gagal menghapus post.']);
        }

        respondJson(200, ['success' => true, 'message' => 'Aspirasi berhasil dihapus.']);
    }

    respondJson(400, ['success' => false, 'message' => 'Aksi tidak dikenali.']);
}

// Fetch query filters and pagination values
$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;

// Load database queries
require __DIR__ . '/queries/get_categories.php';
require __DIR__ . '/queries/get_all_aspirations.php';
require __DIR__ . '/queries/get_statistics.php';

$queryBase = $_GET;
unset($queryBase['page']);

// Handle CSV exports if requested
if (($_GET['export'] ?? '') === 'csv') {
    $exportSql = "
        SELECT
            p.id,
            p.title,
            p.description,
            p.category,
            p.status,
            p.created_at,
            p.updated_at,
            CASE WHEN p.is_anonymous = 1 THEN 'Anonymous' ELSE COALESCE(s.username, 'Mahasiswa') END AS author,
            COALESCE(vt.upvotes, 0) AS upvotes,
            COALESCE(vt.downvotes, 0) AS downvotes
        FROM posts p
        LEFT JOIN students s ON s.id = p.student_id
        LEFT JOIN (
            SELECT post_id,
                SUM(vote_type = 'upvote') AS upvotes,
                SUM(vote_type = 'downvote') AS downvotes
            FROM votes
            GROUP BY post_id
        ) vt ON vt.post_id = p.id
        $whereSql
        ORDER BY $orderSql
    ";
    $exportStmt = $conn->prepare($exportSql);
    $exportParams = $params;
    bindParams($exportStmt, $types, $exportParams);
    $exportStmt->execute();
    $exportRows = $exportStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ruangkita-aspirasi.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Author', 'Category', 'Title', 'Description', 'Status', 'Upvotes', 'Downvotes', 'Created At', 'Updated At']);

    foreach ($exportRows as $exportRow) {
        $meta = statusMeta($exportRow['status']);
        fputcsv($output, [
            $exportRow['id'],
            $exportRow['author'],
            $exportRow['category'],
            $exportRow['title'],
            $exportRow['description'],
            $meta['label'],
            $exportRow['upvotes'],
            $exportRow['downvotes'],
            $exportRow['created_at'],
            $exportRow['updated_at'],
        ]);
    }

    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangKita - Admin Dashboard</title>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/CSS/style.css">
    <link rel="stylesheet" href="../assets/admin/css/admin-dashboard.css">
    <link rel="stylesheet" href="../assets/admin/css/admin-components.css">
    <link rel="stylesheet" href="../assets/admin/css/admin-modal.css">
</head>

<body class="admin-page">
    <!-- Component Header -->
    <?php require __DIR__ . '/components/admin-header.php'; ?>

    <main class="admin-shell">
        <section class="admin-titlebar">
            <div>
                <p class="admin-kicker">Admin Dashboard</p>
                <h1>Kelola Aspirasi Mahasiswa</h1>
                <p class="admin-subtitle">Pantau laporan, prioritaskan aspirasi, dan berikan tanggapan resmi dari satu
                    halaman.</p>
            </div>
            <a class="export-link" href="?<?= e(http_build_query(array_merge($_GET, ['export' => 'csv']))); ?>">Export
                Data</a>
        </section>

        <!-- Component Statistics -->
        <?php require __DIR__ . '/components/statistics-card.php'; ?>

        <!-- Component Filters -->
        <?php require __DIR__ . '/components/filter-bar.php'; ?>

        <section class="admin-feed" aria-label="Daftar aspirasi">
            <?php if (!$posts): ?>
                <div class="empty-state">
                    <h2>Belum ada aspirasi yang cocok</h2>
                    <p>Coba ubah kata kunci, kategori, status, atau urutan filter.</p>
                </div>
            <?php endif; ?>

            <!-- Component Cards List -->
            <?php foreach ($posts as $post): ?>
                <?php require __DIR__ . '/components/aspiration-card.php'; ?>
            <?php endforeach; ?>
        </section>

        <!-- Component Pagination -->
        <?php require __DIR__ . '/components/pagination.php'; ?>
    </main>

    <!-- Component Detailed Popup Dialog -->
    <?php require __DIR__ . '/components/admin-modal.php'; ?>

    <!-- Component Toast Notification -->
    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    <!-- Scripts -->
    <script src="../assets/admin/js/admin-filter.js"></script>
    <script src="../assets/admin/js/admin-theme.js"></script>
    <script src="../assets/admin/js/admin-dropdown.js"></script>
    <script src="../assets/admin/js/admin-status.js"></script>
    <script src="../assets/admin/js/admin-modal.js"></script>
</body>

</html>