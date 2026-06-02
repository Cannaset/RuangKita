<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// ── Detect user type ──────────────────────────────────────
$student = $_SESSION['student'] ?? null;
$admin = $_SESSION['admin'] ?? null;
$department = $_SESSION['department'] ?? null;

if (!$student && !$admin && !$department) {
    header('Location: ../auth/index.php');
    exit;
}

// Unified user object
if ($student) {
    $user = $student;
    $userType = 'student';
    $dashboardUrl = '../students/feed.php';
} elseif ($admin) {
    $user = $admin;
    $userType = 'admin';
    $dashboardUrl = '../admin/dashboard.php';
} else {
    $user = $department;
    $userType = 'department';
    $dashboardUrl = '#';
}

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function getInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $first = strtoupper($parts[0][0] ?? 'U');
    $second = isset($parts[1]) ? strtoupper($parts[1][0]) : '';
    return $first . $second;
}

// ── Flash messages ────────────────────────────────────────
$flash = $_SESSION['flash'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash'], $_SESSION['flash_type']);

// ── Fetch stats from DB ───────────────────────────────────
$stats = ['posts' => 0, 'upvotes' => 0, 'resolved' => 0];

if ($userType === 'student' && $conn) {
    $uid = (int) $user['id'];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM posts WHERE student_id=$uid");
    if ($r)
        $stats['posts'] = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

    $r = mysqli_query($conn, "SELECT COALESCE(SUM(v.upvotes),0) AS c
        FROM posts p
        LEFT JOIN (SELECT post_id, COUNT(*) AS upvotes FROM votes WHERE vote_type='upvote' GROUP BY post_id) v
        ON v.post_id = p.id WHERE p.student_id=$uid");
    if ($r)
        $stats['upvotes'] = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

    $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM posts WHERE student_id=$uid AND status='resolved'");
    if ($r)
        $stats['resolved'] = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

} elseif ($userType === 'admin' && $conn) {
    $uid = (int) $user['id'];
    $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM post_status_logs WHERE changed_by_role='admin' AND changed_by_id=$uid");
    if ($r)
        $stats['posts'] = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

    $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM post_status_logs WHERE changed_by_role='admin' AND changed_by_id=$uid AND new_status='resolved'");
    if ($r)
        $stats['resolved'] = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

} elseif ($userType === 'department' && $conn) {
    $uid = (int) $user['id'];
    $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM post_status_logs WHERE changed_by_role='department' AND changed_by_id=$uid");
    if ($r)
        $stats['posts'] = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

    $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM post_status_logs WHERE changed_by_role='department' AND changed_by_id=$uid AND new_status='resolved'");
    if ($r)
        $stats['resolved'] = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);
}

// ── Recent posts (student only) ───────────────────────────
$recentPosts = [];
if ($userType === 'student' && $conn) {
    $uid = (int) $user['id'];
    $r = mysqli_query($conn, "SELECT id, title, status, category, created_at,
        (SELECT COUNT(*) FROM votes WHERE post_id=posts.id AND vote_type='upvote') AS upvotes
        FROM posts WHERE student_id=$uid ORDER BY created_at DESC LIMIT 5");
    if ($r)
        while ($row = mysqli_fetch_assoc($r))
            $recentPosts[] = $row;
}

$statusColors = [
    'not_reviewed' => ['bg' => '#f59e0b', 'text' => 'Belum Ditinjau'],
    'in_process' => ['bg' => '#3b82f6', 'text' => 'Diproses'],
    'communicated' => ['bg' => '#8b5cf6', 'text' => 'Dikomunikasikan'],
    'resolved' => ['bg' => '#22c55e', 'text' => 'Selesai'],
    'rejected' => ['bg' => '#ef4444', 'text' => 'Ditolak'],
];

$joinedDate = isset($user['created_at'])
    ? date('d M Y', strtotime($user['created_at']))
    : '-';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangKita – Profil</title>
    <link rel="stylesheet" href="../assets/CSS/style.css">
    <link rel="stylesheet" href="../assets/CSS/style-profile.css">
</head>

<body class="profile-page">

    <!-- ── HEADER ─────────────────────────────────────────── -->
    <header class="header">
        <div class="logo">
            <a href="<?= e($dashboardUrl) ?>" style="display:flex;align-items:center;gap:.5rem;text-decoration:none;">
                <img src="../image/RuangKita_Logo6.png" alt="RuangKita" style="height:40px;width:auto;">
            </a>
        </div>
        <div class="header-right">
            <button class="feed-theme-toggle" id="themeToggle" type="button">
                <span id="themeIcon">Dark</span>
            </button>
            <div class="profile-menu">
                <button class="profile-trigger" id="profileTrigger" type="button" aria-expanded="false">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img class="profile-avatar profile-image" src="<?= e($user['profile_picture']) ?>" alt="avatar">
                    <?php else: ?>
                        <span class="profile-avatar"><?= e(getInitials($user['username'])) ?></span>
                    <?php endif; ?>
                </button>
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="profile.php">Profil</a>
                    <a href="../students/feed.php">Beranda</a>
                    <?php if ($userType === 'admin'): ?>
                        <a href="../admin/dashboard.php">Dashboard Admin</a>
                    <?php elseif ($userType === 'department'): ?>
                        <a href="../auth/dashboard-department.php">Dashboard Departemen</a>
                    <?php endif; ?>
                    <a href="../auth/logout.php">Log out</a>
                </div>
            </div>
        </div>
    </header>

    <!-- ── MAIN ───────────────────────────────────────────── -->
    <div class="profile-container">

        <?php if ($flash): ?>
            <div class="flash-msg flash-<?= e($flashType) ?>" id="flashMsg">
                <?= e($flash) ?>
                <button class="flash-close" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endif; ?>

        <!-- CARD UTAMA -->
        <div class="profile-card">

            <!-- LEFT: Avatar + Info -->
            <div class="profile-left">
                <div class="avatar-wrapper" id="avatarWrapper">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img class="avatar-large" id="avatarImg" src="<?= e($user['profile_picture']) ?>" alt="avatar">
                    <?php else: ?>
                        <div class="avatar-large avatar-initials" id="avatarInitials">
                            <?= e(getInitials($user['username'])) ?>
                        </div>
                    <?php endif; ?>
                    <button class="avatar-edit-btn" id="avatarEditBtn" title="Ganti foto profil">
                        ✏️
                    </button>
                    <input type="file" id="avatarInput" accept="image/*" style="display:none">
                </div>

                <div class="profile-name-section">
                    <h2 id="displayName"><?= e($user['username']) ?></h2>
                    <span class="role-badge role-<?= $userType ?>">
                        <?php
                        echo match ($userType) {
                            'student' => '🎓 Mahasiswa',
                            'admin' => '🛡️ Admin',
                            'department' => '🏢 ' . e($user['name'] ?? 'Departemen'),
                            default => 'User'
                        };
                        ?>
                    </span>
                    <p class="joined-date">Bergabung <?= e($joinedDate) ?></p>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <?php if ($userType === 'student'): ?>
                        <div class="stat-item">
                            <span class="stat-num"><?= $stats['posts'] ?></span>
                            <span class="stat-label">Aspirasi</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-num"><?= $stats['upvotes'] ?></span>
                            <span class="stat-label">Upvote Diterima</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-num"><?= $stats['resolved'] ?></span>
                            <span class="stat-label">Selesai</span>
                        </div>
                    <?php else: ?>
                        <div class="stat-item">
                            <span class="stat-num"><?= $stats['posts'] ?></span>
                            <span class="stat-label">Tindakan</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-num"><?= $stats['resolved'] ?></span>
                            <span class="stat-label">Diselesaikan</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT: Tabs -->
            <div class="profile-right">
                <nav class="profile-tabs" role="tablist">
                    <button class="tab-btn active" data-tab="info" role="tab">Informasi</button>
                    <button class="tab-btn" data-tab="edit" role="tab">Edit Profil</button>
                    <button class="tab-btn" data-tab="password" role="tab">Ganti Password</button>
                    <?php if ($userType === 'student'): ?>
                        <button class="tab-btn" data-tab="posts" role="tab">Riwayat Aspirasi</button>
                    <?php endif; ?>
                </nav>

                <!-- TAB: Info -->
                <div class="tab-panel active" id="tab-info" role="tabpanel">
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-label">Username</span>
                            <span class="info-value"><?= e($user['username']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= e($user['email']) ?></span>
                        </div>
                        <?php if ($userType === 'student'): ?>
                            <div class="info-row">
                                <span class="info-label">NIM</span>
                                <span class="info-value"><?= e($user['nim'] ?? '-') ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($userType === 'department'): ?>
                            <div class="info-row">
                                <span class="info-label">Nama Departemen</span>
                                <span class="info-value"><?= e($user['name'] ?? '-') ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-label">Tipe Akun</span>
                            <span class="info-value"><?= ucfirst($userType) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Bergabung</span>
                            <span class="info-value"><?= e($joinedDate) ?></span>
                        </div>
                    </div>
                </div>

                <!-- TAB: Edit Profil -->
                <div class="tab-panel" id="tab-edit" role="tabpanel">
                    <form class="profile-form" id="editProfileForm">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="hidden" name="user_type" value="<?= $userType ?>">
                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">

                        <div class="form-group">
                            <label for="edit_username">Username</label>
                            <input type="text" id="edit_username" name="username" value="<?= e($user['username']) ?>"
                                required maxlength="100">
                        </div>

                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" id="edit_email" name="email" value="<?= e($user['email']) ?>" required
                                maxlength="120">
                        </div>

                        <?php if ($userType === 'student'): ?>
                            <div class="form-group">
                                <label for="edit_nim">NIM <span class="form-hint">(tidak bisa diubah)</span></label>
                                <input type="text" id="edit_nim" value="<?= e($user['nim'] ?? '') ?>" disabled
                                    class="input-disabled">
                            </div>
                        <?php endif; ?>

                        <?php if ($userType === 'department'): ?>
                            <div class="form-group">
                                <label for="edit_deptname">Nama Departemen</label>
                                <input type="text" id="edit_deptname" name="dept_name" value="<?= e($user['name'] ?? '') ?>"
                                    maxlength="150">
                            </div>
                        <?php endif; ?>

                        <div id="editMsg" class="form-msg" style="display:none"></div>
                        <button type="submit" class="btn-primary">Simpan Perubahan</button>
                    </form>
                </div>

                <!-- TAB: Ganti Password -->
                <div class="tab-panel" id="tab-password" role="tabpanel">
                    <form class="profile-form" id="changePasswordForm">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="user_type" value="<?= $userType ?>">
                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">

                        <div class="form-group">
                            <label for="old_password">Password Lama</label>
                            <div class="input-pw-wrap">
                                <input type="password" id="old_password" name="old_password" required>
                                <button type="button" class="pw-toggle" data-target="old_password">👁</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new_password">Password Baru</label>
                            <div class="input-pw-wrap">
                                <input type="password" id="new_password" name="new_password" required minlength="6">
                                <button type="button" class="pw-toggle" data-target="new_password">👁</button>
                            </div>
                            <span class="form-hint">Minimal 6 karakter</span>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password Baru</label>
                            <div class="input-pw-wrap">
                                <input type="password" id="confirm_password" name="confirm_password" required>
                                <button type="button" class="pw-toggle" data-target="confirm_password">👁</button>
                            </div>
                        </div>

                        <div id="pwMsg" class="form-msg" style="display:none"></div>
                        <button type="submit" class="btn-primary">Ganti Password</button>
                    </form>
                </div>

                <!-- TAB: Riwayat (student only) -->
                <?php if ($userType === 'student'): ?>
                    <div class="tab-panel" id="tab-posts" role="tabpanel">
                        <?php if (empty($recentPosts)): ?>
                            <div class="empty-state">
                                <span class="empty-icon">📭</span>
                                <p>Belum ada aspirasi yang dikirim.</p>
                                <a href="create-post.php" class="btn-primary">Buat Aspirasi Pertama</a>
                            </div>
                        <?php else: ?>
                            <div class="posts-list">
                                <?php foreach ($recentPosts as $p):
                                    $sc = $statusColors[$p['status']] ?? ['bg' => '#6b7280', 'text' => $p['status']];
                                    ?>
                                    <div class="post-row">
                                        <div class="post-row-info">
                                            <span class="post-row-cat"><?= e($p['category'] ?? 'Lainnya') ?></span>
                                            <h4 class="post-row-title"><?= e($p['title']) ?></h4>
                                            <small class="post-row-date"><?= date('d M Y', strtotime($p['created_at'])) ?></small>
                                        </div>
                                        <div class="post-row-meta">
                                            <span class="status-pill" style="background:<?= $sc['bg'] ?>">
                                                <?= e($sc['text']) ?>
                                            </span>
                                            <span class="upvote-count">⬆ <?= (int) $p['upvotes'] ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="feed.php" class="see-all-link">Lihat semua aspirasi →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div><!-- /.profile-right -->
        </div><!-- /.profile-card -->

    </div><!-- /.profile-container -->

    <!-- Avatar Upload Modal -->
    <div class="avatar-modal" id="avatarModal" style="display:none">
        <div class="avatar-modal-box">
            <h3>Ganti Foto Profil</h3>
            <div class="avatar-preview-wrap">
                <img id="avatarPreview" src="" alt="Preview" style="display:none">
                <div id="avatarPreviewPlaceholder">Pilih gambar untuk preview</div>
            </div>
            <div class="avatar-modal-actions">
                <button class="btn-secondary" id="avatarCancelBtn">Batal</button>
                <button class="btn-primary" id="avatarSaveBtn" disabled>Simpan Foto</button>
            </div>
        </div>
    </div>

    <script>
        const USER_TYPE = '<?= $userType ?>';
        const USER_ID = <?= (int) $user['id'] ?>;
    </script>
    <script src="../assets/JS/script-profile.js?v=1"></script>
</body>

</html>