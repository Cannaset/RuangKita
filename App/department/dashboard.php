<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/helper.php';

$statusLabels = departmentStatusLabels();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'update_status') {
        require __DIR__ . '/queries/update_status.php';
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
    exit;
}

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$queryBase = $_GET;
unset($queryBase['page']);

require __DIR__ . '/queries/get_statistics.php';
require __DIR__ . '/queries/get_categories.php';
require __DIR__ . '/queries/get_aspirations.php';

$departmentPostDetails = [];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangKita - Dashboard Departemen</title>
    <link rel="stylesheet" href="../assets/CSS/style.css">
    <link rel="stylesheet" href="../assets/CSS/style-admin-dashboard.css?v=3">
    <link rel="stylesheet" href="../assets/admin/css/admin-components.css?v=3">
    <link rel="stylesheet" href="../assets/admin/css/admin-modal.css">
</head>

<body class="admin-page">
    <?php require __DIR__ . '/components/department-header.php'; ?>

    <main class="admin-shell">
        <?php require __DIR__ . '/components/titlebar.php'; ?>
        <?php require __DIR__ . '/components/statistics-card.php'; ?>
        <?php require __DIR__ . '/components/filter-bar.php'; ?>

        <section class="admin-feed" aria-label="Daftar aspirasi">
            <?php if (!$posts): ?>
                <div class="empty-state">
                    <h2>Tidak ada aspirasi yang cocok</h2>
                    <p>Coba pilih kategori atau status lainnya.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($posts as $post): ?>
                <?php require __DIR__ . '/components/aspiration-card.php'; ?>
            <?php endforeach; ?>
        </section>

        <?php require __DIR__ . '/components/pagination.php'; ?>
    </main>

    <?php require __DIR__ . '/components/department-modal.php'; ?>
    <?php require __DIR__ . '/components/toast.php'; ?>

    <script src="../assets/JS/script-dashboard-department.js?v=3"></script>
</body>

</html>
