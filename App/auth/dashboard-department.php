<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['department'])) {
    header('Location: login-department.php');
    exit;
}

$dept = $_SESSION['department'];

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function getInitials(string $name): string {
    $parts  = preg_split('/\s+/', trim($name));
    $first  = strtoupper($parts[0][0] ?? 'U');
    $second = isset($parts[1]) ? strtoupper($parts[1][0]) : '';
    return $first . $second;
}

$deptId = (int)$dept['id'];

// ── Stats ─────────────────────────────────────────────────
$totalAssigned = $inProcess = $resolved = $communicated = 0;

$r = $conn->query("SELECT COUNT(*) AS c FROM posts WHERE status IN ('in_process', 'communicated', 'resolved')");
if ($r) $totalAssigned = (int)($r->fetch_assoc()['c'] ?? 0);

$r = $conn->query("SELECT COUNT(*) AS c FROM posts WHERE status='in_process'");
if ($r) $inProcess = (int)($r->fetch_assoc()['c'] ?? 0);

$r = $conn->query("SELECT COUNT(*) AS c FROM posts WHERE status='communicated'");
if ($r) $communicated = (int)($r->fetch_assoc()['c'] ?? 0);

$r = $conn->query("SELECT COUNT(*) AS c FROM posts WHERE status='resolved'");
if ($r) $resolved = (int)($r->fetch_assoc()['c'] ?? 0);

// ── Posts yang sudah lolos moderasi admin ──────────────────
$search   = trim($_GET['search'] ?? '');
$status   = trim($_GET['status'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;
$offset   = ($page - 1) * $perPage;

$where  = "WHERE p.status IN ('in_process', 'communicated', 'resolved')";
$params = [];
$types  = '';

if ($search !== '') {
    $where   .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= 'ss';
}
if ($status !== '') {
    $where   .= " AND p.status = ?";
    $params[] = $status;
    $types   .= 's';
}

$countSql = "SELECT COUNT(*) AS c
    FROM posts p
    $where";

$stmt = $conn->prepare($countSql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$totalPosts = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$totalPages = max(1, ceil($totalPosts / $perPage));

$sql = "SELECT p.id, p.title, p.description, p.category, p.status, p.created_at,
    p.updated_at, p.image_url, p.is_anonymous, s.nim, s.email,
    CASE WHEN p.is_anonymous=1 THEN 'Anonim' ELSE COALESCE(s.username,'Mahasiswa') END AS author,
    COALESCE(vt.upvotes,0) AS upvotes,
    COALESCE(vt.downvotes,0) AS downvotes,
    COALESCE(ct.comments_count,0) AS comments_count,
    latest_log.new_status AS last_action,
    latest_log.changed_at AS last_action_at
    FROM posts p
    LEFT JOIN students s ON s.id = p.student_id
    LEFT JOIN (
        SELECT post_id,
            SUM(vote_type='upvote') AS upvotes,
            SUM(vote_type='downvote') AS downvotes
        FROM votes GROUP BY post_id
    ) vt ON vt.post_id = p.id
    LEFT JOIN (
        SELECT post_id, COUNT(*) AS comments_count
        FROM comments GROUP BY post_id
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
    LIMIT $perPage OFFSET $offset";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$responsesByPost = [];
if ($posts) {
    $postIds = array_map(fn($post) => (int)$post['id'], $posts);
    $postIdList = implode(',', $postIds);
    $responseSql = "SELECT ar.post_id, ar.response, ar.created_at,
        COALESCE(ad.username, 'Admin') AS admin_name
        FROM admin_responses ar
        LEFT JOIN admins ad ON ad.id = ar.admin_id
        WHERE ar.post_id IN ($postIdList)
        ORDER BY ar.created_at ASC";
    $responseResult = $conn->query($responseSql);

    if ($responseResult) {
        while ($response = $responseResult->fetch_assoc()) {
            $responsesByPost[(int)$response['post_id']][] = $response;
        }
    }
}

// ── Handle status update ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    $postId    = (int)($_POST['post_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $note      = trim($_POST['note'] ?? '');

    $allowed = ['in_process', 'communicated', 'resolved'];
    if (!$postId || !in_array($newStatus, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("SELECT status FROM posts WHERE id = ? LIMIT 1 FOR UPDATE");
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
                'message' => 'Postingan ini belum disetujui admin atau sudah ditolak.'
            ]);
            exit;
        }

        if ($oldStatus === $newStatus) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Status postingan tidak berubah.']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE posts SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $newStatus, $postId);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO post_status_logs (post_id, changed_by_role, changed_by_id, old_status, new_status, note) VALUES (?, 'department', ?, ?, ?, ?)");
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
}

$statusLabels = [
    'not_reviewed' => ['label' => 'Belum Ditinjau', 'color' => '#f59e0b'],
    'in_process'   => ['label' => 'Diproses',       'color' => '#3b82f6'],
    'communicated' => ['label' => 'Dikomunikasikan', 'color' => '#8b5cf6'],
    'resolved'     => ['label' => 'Selesai',         'color' => '#22c55e'],
    'rejected'     => ['label' => 'Ditolak',         'color' => '#ef4444'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangKita – Dashboard Departemen</title>
    <link rel="stylesheet" href="../assets/CSS/style.css">
    <link rel="stylesheet" href="../assets/CSS/style-admin-dashboard.css">
    <link rel="stylesheet" href="../assets/admin/css/admin-components.css">
    <link rel="stylesheet" href="../assets/admin/css/admin-modal.css">
</head>
<body class="admin-page">

<!-- HEADER -->
<header class="header">
    <div class="logo">
        <a href="dashboard-department.php" style="display:flex;align-items:center;text-decoration:none;">
            <img src="../image/RuangKita_Logo6.png" alt="RuangKita" style="height:40px;width:auto;">
        </a>
    </div>
    <div class="header-right">
        <button class="feed-theme-toggle" id="themeToggle" type="button">
            <span id="themeIcon">Dark</span>
        </button>
        <div class="profile-menu">
            <button class="profile-trigger" id="profileTrigger" type="button" aria-expanded="false">
                <?php if (!empty($dept['profile_picture'])): ?>
                    <img class="profile-avatar profile-image" src="<?= e($dept['profile_picture']) ?>" alt="avatar">
                <?php else: ?>
                    <span class="profile-avatar"><?= e(getInitials($dept['name'])) ?></span>
                <?php endif; ?>
            </button>
            <div class="profile-dropdown" id="profileDropdown">
                <a href="../students/profile.php">Profil</a>
                <a href="dashboard-department.php">Dashboard</a>
                <a href="../students/feed.php">Beranda</a>
                <a href="logout.php">Log out</a>
            </div>
        </div>
    </div>
</header>

<main class="admin-shell">

    <!-- TITLE -->
    <section class="admin-titlebar">
        <div>
            <p class="admin-kicker">Departemen – <?= e($dept['name']) ?></p>
            <h1>Dashboard Departemen</h1>
            <p class="admin-subtitle">Pantau dan perbarui status aspirasi yang ditangani departemen kamu.</p>
        </div>
    </section>

    <!-- STATS -->
    <div class="stats-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem;">
        <?php foreach ([
            ['label' => 'Total Disetujui', 'value' => $totalAssigned, 'color' => '#008891'],
            ['label' => 'Diproses',        'value' => $inProcess,     'color' => '#3b82f6'],
            ['label' => 'Dikomunikasikan', 'value' => $communicated,  'color' => '#8b5cf6'],
            ['label' => 'Selesai',         'value' => $resolved,      'color' => '#22c55e'],
        ] as $stat): ?>
        <div style="background:white;border-radius:.75rem;padding:1.25rem 1.5rem;border:1px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,.05);">
            <p style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin:0 0 .5rem;"><?= $stat['label'] ?></p>
            <p style="font-size:2rem;font-weight:800;color:<?= $stat['color'] ?>;margin:0;"><?= $stat['value'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- FILTER -->
    <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.5rem;">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari judul..."
            style="flex:1;min-width:200px;padding:.6rem 1rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:.9rem;">
        <select name="status" style="padding:.6rem 1rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:.9rem;">
            <option value="">Semua Status</option>
            <?php foreach (array_intersect_key($statusLabels, array_flip(['in_process', 'communicated', 'resolved'])) as $key => $s): ?>
                <option value="<?= $key ?>" <?= $status === $key ? 'selected' : '' ?>><?= $s['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" style="padding:.6rem 1.25rem;background:#008891;color:white;border:none;border-radius:.5rem;font-weight:700;cursor:pointer;">Cari</button>
        <?php if ($search || $status): ?>
            <a href="dashboard-department.php" style="padding:.6rem 1rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:.9rem;text-decoration:none;color:#374151;">Reset</a>
        <?php endif; ?>
    </form>

    <!-- POST LIST -->
    <section class="admin-feed">
        <?php $departmentPostDetails = []; ?>
        <?php if (empty($posts)): ?>
            <div class="empty-state" style="text-align:center;padding:3rem;color:#9ca3af;">
                <p style="font-size:1.1rem;">Belum ada aspirasi yang disetujui admin.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($posts as $post):
            $sl = $statusLabels[$post['status']] ?? ['label' => $post['status'], 'color' => '#6b7280'];
            $attachmentUrl = trim((string)($post['image_url'] ?? ''));
            $attachmentPath = parse_url($attachmentUrl, PHP_URL_PATH) ?: '';
            $attachmentExtension = strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION));
            $isVideoAttachment = in_array($attachmentExtension, ['mp4', 'webm', 'ogg'], true);
            $description = trim((string)$post['description']);
            $descriptionLength = function_exists('mb_strlen') ? mb_strlen($description) : strlen($description);
            $descriptionExcerpt = $descriptionLength > 180
                ? (function_exists('mb_substr') ? mb_substr($description, 0, 180) : substr($description, 0, 180)) . '...'
                : $description;
            $priorityClass = (int)$post['upvotes'] >= 25
                ? 'priority-high'
                : ((int)$post['upvotes'] >= 10 ? 'priority-medium' : 'priority-normal');
            $priorityLabel = (int)$post['upvotes'] >= 25
                ? 'Trending'
                : ((int)$post['upvotes'] >= 10 ? 'Prioritas' : 'Normal');
            $postDetail = [
                'id' => (int)$post['id'],
                'title' => $post['title'],
                'description' => $post['description'],
                'category' => $post['category'] ?: 'Lainnya',
                'statusLabel' => $sl['label'],
                'statusColor' => $sl['color'],
                'author' => $post['author'],
                'nim' => (int)$post['is_anonymous'] === 1 ? '-' : ($post['nim'] ?? '-'),
                'email' => (int)$post['is_anonymous'] === 1 ? '-' : ($post['email'] ?? '-'),
                'createdAt' => $post['created_at'],
                'updatedAt' => $post['updated_at'],
                'upvotes' => (int)$post['upvotes'],
                'downvotes' => (int)$post['downvotes'],
                'comments' => (int)$post['comments_count'],
                'imageUrl' => $attachmentUrl,
                'responses' => $responsesByPost[(int)$post['id']] ?? [],
            ];
            $departmentPostDetails[(int)$post['id']] = $postDetail;
        ?>
        <article class="aspiration-card admin-post-card">
            <div class="post-card-top">
                <div class="author-cluster">
                    <span class="admin-avatar"><?= e(getInitials($post['author'])) ?></span>
                    <div>
                        <h2><?= e($post['title']) ?></h2>
                        <p><?= e($post['author']) ?> &middot; <?= e($post['category'] ?: 'Lainnya') ?></p>
                    </div>
                </div>
                <span class="admin-status-badge" style="background:<?= e($sl['color']) ?>;">
                    <?= e($sl['label']) ?>
                </span>
            </div>

            <p class="post-excerpt"><?= e($descriptionExcerpt) ?></p>

            <div class="post-meta-row">
                <span><?= e(date('d M Y, H:i', strtotime($post['created_at']))) ?></span>
                <span><?= (int)$post['upvotes'] ?> upvote</span>
                <span><?= (int)$post['comments_count'] ?> komentar</span>
                <span class="priority-pill <?= e($priorityClass) ?>"><?= e($priorityLabel) ?></span>
            </div>

            <?php if ($attachmentUrl !== ''): ?>
                <div class="attachment-preview">
                    <?php if ($isVideoAttachment): ?>
                        <video src="<?= e($attachmentUrl) ?>" controls preload="metadata"></video>
                    <?php else: ?>
                        <img src="<?= e($attachmentUrl) ?>" alt="Lampiran aspirasi <?= e($post['title']) ?>" loading="lazy">
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="admin-card-actions" style="grid-template-columns:minmax(0,1fr) auto;">
                <?php if (!in_array($post['status'], ['resolved', 'rejected'], true)): ?>
                    <div class="status-form">
                        <label for="department-status-<?= (int)$post['id'] ?>">Status</label>
                        <select id="department-status-<?= (int)$post['id'] ?>" class="status-select"
                            data-post-id="<?= (int)$post['id'] ?>">
                            <option value="">-- Ubah Status --</option>
                            <option value="in_process" <?= $post['status'] === 'in_process' ? 'selected' : '' ?>>Diproses</option>
                            <option value="communicated" <?= $post['status'] === 'communicated' ? 'selected' : '' ?>>Dikomunikasikan</option>
                            <option value="resolved" <?= $post['status'] === 'resolved' ? 'selected' : '' ?>>Selesai</option>
                        </select>
                        <input type="text" class="note-input" placeholder="Catatan (opsional)"
                            style="min-height:2.6rem;padding:0 .9rem;border:1px solid #d1d5db;border-radius:.5rem;font:inherit;">
                        <button class="save-status-btn" data-post-id="<?= (int)$post['id'] ?>"
                            style="min-height:2.4rem;padding:.5rem 1rem;background:#008891;color:white;border:none;border-radius:.5rem;font-size:.85rem;font-weight:700;cursor:pointer;">
                            Simpan
                        </button>
                    </div>
                <?php else: ?>
                    <div class="status-form">
                        <label>Status</label>
                        <p style="margin:0;color:#6b7280;font-size:.85rem;">Status aspirasi sudah final.</p>
                    </div>
                <?php endif; ?>
                <button type="button" class="detail-post-btn detail-button"
                    data-post-id="<?= (int)$post['id'] ?>">Detail</button>
            </div>

            <?php $postResponses = $responsesByPost[(int)$post['id']] ?? []; ?>
            <?php if ($postResponses): ?>
                <?php $latestResponse = $postResponses[count($postResponses) - 1]; ?>
                <div class="latest-response">
                    <strong>Tanggapan admin terbaru</strong>
                    <p><?= e($latestResponse['response']) ?></p>
                </div>
            <?php endif; ?>
        </article>

        <?php if (false): ?>
        <div class="aspiration-card"
            data-post="<?= e(json_encode($postDetail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
            style="background:white;border-radius:.75rem;border:1px solid #e5e7eb;padding:1.25rem 1.5rem;margin-bottom:1rem;box-shadow:0 2px 8px rgba(0,0,0,.04);">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;gap:.5rem;align-items:center;margin-bottom:.4rem;flex-wrap:wrap;">
                        <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:#008891;"><?= e($post['category'] ?? 'Lainnya') ?></span>
                        <span style="display:inline-block;padding:.2rem .65rem;border-radius:999px;font-size:.68rem;font-weight:700;color:white;background:<?= $sl['color'] ?>;">
                            <?= e($sl['label']) ?>
                        </span>
                        <span style="font-size:.7rem;color:#9ca3af;">•</span>
                        <span style="font-size:.7rem;color:#9ca3af;"><?= e($post['author']) ?></span>
                        <span style="font-size:.7rem;color:#9ca3af;">•</span>
                        <span style="font-size:.7rem;color:#9ca3af;"><?= date('d M Y', strtotime($post['created_at'])) ?></span>
                    </div>
                    <h3 style="margin:0 0 .5rem;font-size:1rem;font-weight:700;color:#111827;"><?= e($post['title']) ?></h3>
                    <div style="display:flex;align-items:center;gap:.65rem;flex-wrap:wrap;">
                        <button type="button" class="detail-post-btn"
                            style="padding:.35rem .85rem;background:#fff;color:#008891;border:1px solid #008891;border-radius:.45rem;font-size:.78rem;font-weight:700;cursor:pointer;">
                            Detail
                        </button>
                        <span style="font-size:.8rem;color:#6b7280;"><?= (int)$post['comments_count'] ?> komentar</span>
                        <?php if (!empty($responsesByPost[(int)$post['id']])): ?>
                            <span style="font-size:.8rem;color:#008891;font-weight:600;">
                                <?= count($responsesByPost[(int)$post['id']]) ?> tanggapan admin
                            </span>
                        <?php endif; ?>
                        <span style="font-size:.8rem;color:#6b7280;">⬆ <?= (int)$post['upvotes'] ?></span>
                    </div>
                    <?php if ($descriptionExcerpt !== ''): ?>
                        <p style="max-width:720px;margin:.75rem 0 0;padding:.7rem .85rem;border-left:3px solid #008891;border-radius:.25rem;background:#f8fafc;color:#6b7280;font-size:.84rem;line-height:1.55;">
                            <?= e($descriptionExcerpt) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Update Status -->
                <?php if (!in_array($post['status'], ['resolved', 'rejected'])): ?>
                <div style="display:flex;flex-direction:column;gap:.5rem;min-width:200px;">
                    <select class="status-select" data-post-id="<?= $post['id'] ?>"
                        style="padding:.5rem .75rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:.85rem;">
                        <option value="">-- Ubah Status --</option>
                        <option value="in_process"   <?= $post['status']==='in_process'   ? 'selected':'' ?>>Diproses</option>
                        <option value="communicated" <?= $post['status']==='communicated' ? 'selected':'' ?>>Dikomunikasikan</option>
                        <option value="resolved"     <?= $post['status']==='resolved'     ? 'selected':'' ?>>Selesai</option>
                    </select>
                    <input type="text" class="note-input" placeholder="Catatan (opsional)"
                        style="padding:.5rem .75rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:.85rem;">
                    <button class="save-status-btn" data-post-id="<?= $post['id'] ?>"
                        style="padding:.5rem 1rem;background:#008891;color:white;border:none;border-radius:.5rem;font-size:.85rem;font-weight:700;cursor:pointer;">
                        Simpan
                    </button>
                </div>
                <?php else: ?>
                <span style="font-size:.8rem;color:#9ca3af;font-style:italic;">Status final</span>
                <?php endif; ?>
            </div>

            <?php if ($attachmentUrl !== ''): ?>
                <div class="attachment-preview" style="margin-top:1rem;">
                    <?php if ($isVideoAttachment): ?>
                        <video src="<?= e($attachmentUrl) ?>" controls preload="metadata">
                            Browser tidak mendukung pemutaran video.
                        </video>
                    <?php else: ?>
                        <img src="<?= e($attachmentUrl) ?>" alt="Lampiran aspirasi <?= e($post['title']) ?>" loading="lazy">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </section>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:.5rem;margin-top:1.5rem;flex-wrap:wrap;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                style="padding:.5rem .9rem;border-radius:.4rem;border:1px solid <?= $i===$page ? '#008891':'#d1d5db' ?>;background:<?= $i===$page ? '#008891':'white' ?>;color:<?= $i===$page ? 'white':'#374151' ?>;text-decoration:none;font-weight:600;font-size:.85rem;">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

</main>

<div class="admin-modal" id="departmentPostModal" hidden>
    <div class="admin-modal-panel" role="dialog" aria-modal="true" aria-labelledby="departmentModalTitle">
        <button class="modal-close" id="departmentModalClose" type="button" aria-label="Tutup detail">&times;</button>
        <div class="modal-body" id="departmentModalBody"></div>
    </div>
</div>

<!-- Toast -->
<div id="deptToast" style="position:fixed;bottom:1.5rem;right:1.5rem;padding:.85rem 1.25rem;border-radius:.6rem;font-size:.9rem;font-weight:600;color:white;z-index:9999;display:none;box-shadow:0 8px 24px rgba(0,0,0,.2);"></div>

<script>
const departmentPosts = <?= json_encode(
    $departmentPostDetails,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
) ?>;

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatDate(value) {
    if (!value) return '-';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return escapeHtml(value);

    return escapeHtml(date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }));
}

function renderDetailMedia(url) {
    if (!url) return '';
    const safeUrl = escapeHtml(url);
    const path = String(url).split(/[?#]/)[0].toLowerCase();
    const isVideo = ['.mp4', '.webm', '.ogg'].some(extension => path.endsWith(extension));

    return `<div class="modal-media">${
        isVideo
            ? `<video controls preload="metadata" src="${safeUrl}"></video>`
            : `<img src="${safeUrl}" alt="Lampiran aspirasi">`
    }</div>`;
}

function renderAdminResponses(responses) {
    if (!Array.isArray(responses) || responses.length === 0) {
        return '<p class="modal-description">Belum ada tanggapan resmi untuk aspirasi ini.</p>';
    }

    return `<div class="response-list">${responses.map(response => `
        <article class="response-item">
            <strong>${escapeHtml(response.admin_name || 'Admin')}</strong>
            <small>${formatDate(response.created_at)}</small>
            <p>${escapeHtml(response.response)}</p>
        </article>
    `).join('')}</div>`;
}

// Detail post
(function() {
    const modal = document.getElementById('departmentPostModal');
    const modalBody = document.getElementById('departmentModalBody');
    const closeButton = document.getElementById('departmentModalClose');

    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.detail-post-btn').forEach(button => {
        button.addEventListener('click', () => {
            const postId = button.dataset.postId;
            const post = departmentPosts[postId];
            if (!post) {
                showToast('Detail postingan tidak ditemukan.', 'error');
                return;
            }

            modalBody.innerHTML = `
                <h2 class="modal-title" id="departmentModalTitle">${escapeHtml(post.title)}</h2>
                <div class="modal-meta">
                    <span>${escapeHtml(post.author)}</span>
                    <span>${escapeHtml(post.category)}</span>
                    <span>${formatDate(post.createdAt)}</span>
                    <span>${Number(post.upvotes || 0)} upvote</span>
                    <span>${Number(post.downvotes || 0)} downvote</span>
                    <span>${Number(post.comments || 0)} komentar</span>
                    <span class="admin-status-badge" style="background:${escapeHtml(post.statusColor)};color:#fff;">
                        ${escapeHtml(post.statusLabel)}
                    </span>
                </div>
                <p class="modal-description">${escapeHtml(post.description)}</p>
                ${renderDetailMedia(post.imageUrl)}
                <h3 class="modal-section-title">Informasi Mahasiswa</h3>
                <div class="modal-meta">
                    <span>NIM: ${escapeHtml(post.nim)}</span>
                    <span>Email: ${escapeHtml(post.email)}</span>
                    <span>Update terakhir: ${formatDate(post.updatedAt)}</span>
                </div>
                <h3 class="modal-section-title">Tanggapan Admin</h3>
                ${renderAdminResponses(post.responses)}
            `;

            modal.hidden = false;
            document.body.style.overflow = 'hidden';
        });
    });

    closeButton.addEventListener('click', closeModal);
    modal.addEventListener('click', event => {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !modal.hidden) closeModal();
    });
})();

// Theme
(function() {
    const isDark = localStorage.getItem('ruangkita-theme') === 'dark';
    if (isDark) document.body.classList.add('dark-theme');
    document.getElementById('themeIcon').textContent = isDark ? 'Light' : 'Dark';
    document.getElementById('themeToggle').addEventListener('click', () => {
        const dark = document.body.classList.toggle('dark-theme');
        localStorage.setItem('ruangkita-theme', dark ? 'dark' : 'light');
        document.getElementById('themeIcon').textContent = dark ? 'Light' : 'Dark';
    });
})();

// Profile dropdown
(function() {
    const menu    = document.querySelector('.profile-menu');
    const trigger = document.getElementById('profileTrigger');
    if (!menu || !trigger) return;
    trigger.addEventListener('click', e => {
        e.stopPropagation();
        menu.classList.toggle('open');
    });
    document.addEventListener('click', e => {
        if (!menu.contains(e.target)) menu.classList.remove('open');
    });
})();

// Save status
document.querySelectorAll('.save-status-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const postId = btn.dataset.postId;
        const card   = btn.closest('.aspiration-card');
        const status = card.querySelector('.status-select').value;
        const note   = card.querySelector('.note-input').value;

        if (!status) {
            showToast('Pilih status terlebih dahulu.', 'error');
            return;
        }

        btn.disabled    = true;
        btn.textContent = 'Menyimpan...';

        const fd = new FormData();
        fd.append('action',  'update_status');
        fd.append('post_id', postId);
        fd.append('status',  status);
        fd.append('note',    note);

        try {
            const res  = await fetch('dashboard-department.php', { method: 'POST', body: fd });
            const data = await res.json();
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) setTimeout(() => location.reload(), 1000);
        } catch {
            showToast('Gagal menghubungi server.', 'error');
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Simpan';
        }
    });
});

function showToast(msg, type = 'success') {
    const t = document.getElementById('deptToast');
    t.textContent   = msg;
    t.style.background = type === 'success' ? '#22c55e' : '#ef4444';
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3000);
}
</script>
</body>
</html>
