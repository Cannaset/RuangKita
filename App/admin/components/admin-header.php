<?php
// ============================================================
// RuangKita - Component Admin Header
// File: App/admin/components/admin-header.php
// ============================================================
?>
<header class="header">
    <div class="logo">
        <a href="dashboard.php" class="admin-logo-link">
            <img src="../image/RuangKita_Logo6.png" alt="RuangKita">
        </a>
    </div>

    <div class="header-right">
        <button class="feed-theme-toggle" id="themeToggle" type="button" aria-label="Ganti tema">
            <span id="themeIcon">Dark</span>
        </button>

        <div class="profile-menu">
            <button class="profile-trigger" id="profileTrigger" type="button" aria-label="Menu admin"
                aria-expanded="false">
                <?php if (!empty($admin['profile_picture'])): ?>
                    <img class="profile-avatar profile-image" src="<?= e($admin['profile_picture']); ?>"
                        alt="<?= e($admin['username'] ?? 'Admin'); ?>">
                <?php else: ?>
                    <span class="profile-avatar" title="<?= e($admin['username'] ?? 'Admin'); ?>">
                        <?= e(getInitials($admin['username'] ?? 'Admin')); ?>
                    </span>
                <?php endif; ?>
            </button>

            <div class="profile-dropdown" id="profileDropdown">
                <a href="../students/profile.php">Profil</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="../students/feed.php">Beranda</a>
                <a href="../auth/logout.php">Log out</a>
            </div>
        </div>
    </div>
    <script>
        document.querySelectorAll('a[href*="logout"]').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                if (confirm('Yakin ingin keluar dari RuangKita?')) {
                    window.location.href = link.href;
                }
            });
        });
    </script>
</header>