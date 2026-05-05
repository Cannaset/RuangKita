<?php
session_start();

$student = $_SESSION['student'] ?? null;

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function getInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $first = $parts[0][0] ?? 'U';
    $second = isset($parts[1]) ? $parts[1][0] : '';

    return strtoupper($first . $second);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangKita - Feed</title>
    <link rel="stylesheet" href="../CSS/style.css?v=7">
    <link rel="stylesheet" href="../CSS/style-feed.css?v=7">
</head>

<body class="feed-page">

    <!-- HEADER -->
    <header class="header">
        <div class="logo">
            <img src="../image/RuangKita_Logo6.png" alt="RuangKita" style="height: 40px; width: auto;">
        </div>

        <div class="header-right">
            <button class="feed-theme-toggle" id="themeToggle" type="button" aria-label="Ganti tema">
                <span id="themeIcon">Dark</span>
            </button>
            <button class="notification-btn" type="button" aria-label="Notifikasi">
                <img
                    id="notificationIcon"
                    src="../image/Notification_Dark.png"
                    data-light-src="../image/Notification_Dark.png"
                    data-dark-src="../image/Notification_Dark.png"
                    alt="Notifikasi"
                >
            </button>

            <?php if ($student): ?>
                <div class="profile-menu">
                    <button class="profile-trigger" id="profileTrigger" type="button" aria-label="Menu profil" aria-expanded="false">
                        <?php if (!empty($student['profile_picture'])): ?>
                            <img class="profile-avatar profile-image" src="<?= e($student['profile_picture']); ?>" alt="<?= e($student['username']); ?>">
                        <?php else: ?>
                            <span class="profile-avatar" title="<?= e($student['username']); ?>">
                                <?= e(getInitials($student['username'])); ?>
                            </span>
                        <?php endif; ?>
                    </button>

                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="#">Profil</a>
                        <a href="logout.php">Log out</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-links">
                    <a href="index.php">Login</a>
                    <span>||</span>
                    <a href="signup.php">Signup</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- MAIN CONTAINER -->
    <div class="container">

        <!-- SEARCH -->
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search posts...">
        </div>

        <!-- FILTER NAV -->
        <nav class="filter-nav" id="filterNav">
            <button class="filter-btn active" data-category="All">All</button>
            <button class="filter-btn" data-category="Facilities">Facilities</button>
            <button class="filter-btn" data-category="Academic">Academic</button>
            <button class="filter-btn" data-category="Cleanliness">Cleanliness</button>
            <button class="filter-btn" data-category="Other">Other</button>
        </nav>

        <!-- SORT NAV -->
        <nav class="sort-nav" id="sortNav">
            <button class="sort-btn active" data-sort="Newest">Newest</button>
            <button class="sort-btn" data-sort="Popular">Popular</button>
            <button class="sort-btn" data-sort="Unresolved">Unresolved</button>
        </nav>

        <!-- FEED -->
        <main class="feed" id="feedContainer"></main>

    </div>

    <div id="imageModal" class="image-modal" style="display: none;">
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            <img id="modalImage" src="" alt="Full size image">
        </div>
    </div>

    <script src="../JS/script-feed.js?v=7"></script>
</body>

</html>
