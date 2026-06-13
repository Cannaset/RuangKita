<?php
// Department dashboard header.
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
            <button class="profile-trigger" id="profileTrigger" type="button" aria-label="Menu departemen"
                aria-expanded="false">
                <?php if (!empty($dept['profile_picture'])): ?>
                    <img class="profile-avatar profile-image" src="<?= e($dept['profile_picture']); ?>"
                        alt="<?= e($dept['name']); ?>">
                <?php else: ?>
                    <span class="profile-avatar" title="<?= e($dept['name']); ?>">
                        <?= e(getInitials($dept['name'])); ?>
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
</header>
