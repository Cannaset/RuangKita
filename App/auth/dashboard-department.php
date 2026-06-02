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

$r = $conn->query("SELECT COUNT(*) AS c FROM post_status_logs WHERE changed_by_role='department' AND changed_by_id=$deptId");
if ($r) $totalAssigned = (int)($r->fetch_assoc()['c'] ?? 0);

$r = $conn->query("SELECT COUNT(*) AS c FROM post_status_logs WHERE changed_by_role='department' AND changed_by_id=$deptId AND new_status='in_process'");
if ($r) $inProcess = (int)($r->fetch_assoc()['c'] ?? 0);

$r = $conn->query("SELECT COUNT(*) AS c FROM post_status_logs WHERE changed_by_role='department' AND changed_by_id=$deptId AND new_status='communicated'");
if ($r) $communicated = (int)($r->fetch_assoc()['c'] ?? 0);

$r = $conn->query("SELECT COUNT(*) AS c FROM post_status_logs WHERE changed_by_role='department' AND changed_by_id=$deptId AND new_status='resolved'");
if ($r) $resolved = (int)($r->fetch_assoc()['c'] ?? 0);

// ── Posts yang pernah ditangani department ini ─────────────
$search   = trim($_GET['search'] ?? '');
$status   = trim($_GET['status'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;
$offset   = ($page - 1) * $perPage;

$where  = "WHERE psl.changed_by_role='department' AND psl.changed_by_id=$deptId";
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

$countSql = "SELECT COUNT(DISTINCT p.id) AS c
    FROM post_status_logs psl
    JOIN posts p ON p.id = psl.post_id
    $where";

$stmt = $conn->prepare($countSql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$totalPosts = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$totalPages = max(1, ceil($totalPosts / $perPage));

$sql = "SELECT DISTINCT p.id, p.title, p.category, p.status, p.created_at,
    CASE WHEN p.is_anonymous=1 THEN 'Anonim' ELSE COALESCE(s.username,'Mahasiswa') END AS author,
    COALESCE(vt.upvotes,0) AS upvotes,
    psl.new_status AS last_action,
    psl.changed_at AS last_action_at
    FROM post_status_logs psl
    JOIN posts p ON p.id = psl.post_id
    LEFT JOIN students s ON s.id = p.student_id
    LEFT JOIN (
        SELECT post_id, SUM(vote_type='upvote') AS upvotes
        FROM votes GROUP BY post_id
    ) vt ON vt.post_id = p.id
    $where
    ORDER BY psl.changed_at DESC
    LIMIT $perPage OFFSET $offset";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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

    $r = $conn->query("SELECT status FROM posts WHERE id=$postId LIMIT 1");
    $oldStatus = $r ? ($r->fetch_assoc()['status'] ?? '') : '';

    $conn->query("UPDATE posts SET status='$newStatus', updated_at=NOW() WHERE id=$postId");

    $stmt = $conn->prepare("INSERT INTO post_status_logs (post_id, changed_by_role, changed_by_id, old_status, new_status, note) VALUES (?, 'department', ?, ?, ?, ?)");
    $stmt->bind_param('iisss', $postId, $deptId, $oldStatus, $newStatus, $note);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui.']);
    exit;
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
            ['label' => 'Total Ditangani', 'value' => $totalAssigned, 'color' => '#008891'],
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
            <?php foreach ($statusLabels as $key => $s): ?>
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
        <?php if (empty($posts)): ?>
            <div class="empty-state" style="text-align:center;padding:3rem;color:#9ca3af;">
                <p style="font-size:1.1rem;">Belum ada aspirasi yang ditangani.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($posts as $post):
            $sl = $statusLabels[$post['status']] ?? ['label' => $post['status'], 'color' => '#6b7280'];
        ?>
        <div class="aspiration-card" style="background:white;border-radius:.75rem;border:1px solid #e5e7eb;padding:1.25rem 1.5rem;margin-bottom:1rem;box-shadow:0 2px 8px rgba(0,0,0,.04);">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;gap:.5rem;align-items:center;margin-bottom:.4rem;flex-wrap:wrap;">
                        <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:#008891;"><?= e($post['category'] ?? 'Lainnya') ?></span>
                        <span style="font-size:.7rem;color:#9ca3af;">•</span>
                        <span style="font-size:.7rem;color:#9ca3af;"><?= e($post['author']) ?></span>
                        <span style="font-size:.7rem;color:#9ca3af;">•</span>
                        <span style="font-size:.7rem;color:#9ca3af;"><?= date('d M Y', strtotime($post['created_at'])) ?></span>
                    </div>
                    <h3 style="margin:0 0 .5rem;font-size:1rem;font-weight:700;color:#111827;"><?= e($post['title']) ?></h3>
                    <div style="display:flex;align-items:center;gap:.75rem;">
                        <span style="display:inline-block;padding:.2rem .7rem;border-radius:999px;font-size:.7rem;font-weight:700;color:white;background:<?= $sl['color'] ?>;">
                            <?= $sl['label'] ?>
                        </span>
                        <span style="font-size:.8rem;color:#6b7280;">⬆ <?= (int)$post['upvotes'] ?></span>
                    </div>
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
        </div>
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

<!-- Toast -->
<div id="deptToast" style="position:fixed;bottom:1.5rem;right:1.5rem;padding:.85rem 1.25rem;border-radius:.6rem;font-size:.9rem;font-weight:600;color:white;z-index:9999;display:none;box-shadow:0 8px 24px rgba(0,0,0,.2);"></div>

<script>
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