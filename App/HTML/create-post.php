<?php
session_start();

$student = $_SESSION['student'] ?? null;

// Redirect if not logged in
if (!$student) {
    header('Location: index.php');
    exit;
}

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

$categories = [
    'Facilities' => 'Fasilitas',
    'Academic' => 'Akademik',
    'Cleanliness' => 'Kebersihan',
    'Other' => 'Lainnya'
];

$submitSuccess = false;
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = trim($_POST['category'] ?? '');
    
    // Validation
    if (empty($title)) {
        $errorMessage = 'Judul tidak boleh kosong';
    } elseif (empty($content)) {
        $errorMessage = 'Deskripsi tidak boleh kosong';
    } elseif (empty($category) || !array_key_exists($category, $categories)) {
        $errorMessage = 'Kategori tidak valid';
    } else {
        // TODO: API call akan dilakukan di sini
        // Placeholder untuk sekarang
        $submitSuccess = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangKita - Create Post</title>
    <link rel="stylesheet" href="../CSS/style.css?v=7">
    <link rel="stylesheet" href="../CSS/style-create-post.css?v=7">
</head>

<body class="create-post-page">

    <!-- HEADER -->
    <header class="header">
        <div class="logo">
            <a href="feed.php" style="text-decoration: none; color: white; display: flex; align-items: center; gap: 0.5rem;">
                <img src="../image/RuangKita_Logo6.png" alt="RuangKita" style="height: 40px; width: auto;">
            </a>
        </div>

        <div class="header-right">
            <button class="feed-theme-toggle" id="themeToggle" type="button" aria-label="Ganti tema">
                <span id="themeIcon">Dark</span>
            </button>

            <?php if ($student): ?>
                <div class="profile-menu">
                    <button class="profile-trigger" id="profileTrigger" type="button" aria-label="Menu profil" aria-expanded="false">
                        <span class="profile-avatar" title="<?= e($student['username']); ?>">
                            <?= e(getInitials($student['username'])); ?>
                        </span>
                    </button>

                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="#">Profil</a>
                        <a href="logout.php">Log out</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- MAIN CONTAINER -->
    <div class="container">
        <div class="create-post-wrapper">
            
            <!-- BACK BUTTON -->
            <a href="feed.php" class="back-button">← Kembali ke Feed</a>

            <!-- FORM CARD -->
            <div class="post-form-card">
                <h1>Sampaikan Aspirasi Kamu</h1>
                <p class="subtitle">Bantu kami memahami apa yang perlu diperbaiki</p>

                <?php if ($submitSuccess): ?>
                    <div class="success-message">
                        ✓ Aspirasi kamu berhasil dikirim! Terima kasih atas kontribusimu.
                    </div>
                    <div style="margin-top: 1.5rem;">
                        <a href="feed.php" class="btn btn-primary">Kembali ke Feed</a>
                    </div>
                <?php else: ?>
                    <form id="createPostForm" method="POST" action="create-post.php" novalidate enctype="multipart/form-data">

                        <!-- TITLE -->
                        <div class="form-group">
                            <label for="title">Judul Aspirasi <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="title" 
                                name="title" 
                                placeholder="Ringkas masalah yang ingin disampaikan"
                                value="<?= e($_POST['title'] ?? ''); ?>"
                                maxlength="200"
                            >
                            <small class="char-count"><span id="titleCount">0</span>/200</small>
                        </div>

                        <!-- CATEGORY -->
                        <div class="form-group">
                            <label for="category">Kategori <span class="required">*</span></label>
                            <select id="category" name="category">
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $key => $label): ?>
                                    <option value="<?= e($key); ?>" <?= isset($_POST['category']) && $_POST['category'] === $key ? 'selected' : ''; ?>>
                                        <?= e($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- CONTENT -->
                        <div class="form-group">
                            <label for="content">Deskripsi Detail <span class="required">*</span></label>
                            <textarea 
                                id="content" 
                                name="content" 
                                placeholder="Jelaskan masalah secara detail. Semakin detail semakin membantu kami."
                                rows="8"
                                maxlength="2000"
                            ><?= e($_POST['content'] ?? ''); ?></textarea>
                            <small class="char-count"><span id="contentCount">0</span>/2000</small>
                        </div>

                        <!-- IMAGE UPLOAD -->
                        <div class="form-group">
                            <label for="image">Lampirkan Foto/Video (Opsional)</label>
                            <div class="file-upload-area" id="fileUploadArea">
                                <input 
                                    type="file" 
                                    id="image" 
                                    name="image" 
                                    accept="image/*,video/*"
                                    style="display: none;"
                                >
                                <div class="upload-placeholder">
                                    <span class="upload-icon">📎</span>
                                    <p>Klik atau drag file ke sini</p>
                                    <small>Maksimal 10MB (JPG, PNG, MP4)</small>
                                </div>
                            </div>
                            <div id="filePreview" style="margin-top: 1rem; display: none;">
                                <img id="previewImg" src="" alt="Preview" style="max-width: 100%; max-height: 300px; border-radius: 0.5rem;">
                            </div>
                        </div>

                        <!-- ANONYMOUS CHECKBOX -->
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="anonymous" name="anonymous">
                            <label for="anonymous" class="checkbox-label">Sampaikan secara anonim</label>
                        </div>

                        <!-- ERROR MESSAGE -->
                        <?php if ($errorMessage): ?>
                            <div class="error-message">
                                ✕ <?= e($errorMessage); ?>
                            </div>
                        <?php endif; ?>

                        <!-- BUTTONS -->
                        <div class="form-actions">
                            <a href="feed.php" class="btn btn-secondary">Batalkan</a>
                            <button type="submit" class="btn btn-primary">Kirim Aspirasi</button>
                        </div>

                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../JS/script-create-post.js?v=7"></script>
</body>

</html>