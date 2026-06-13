<?php
$statusMeta = $statusLabels[$post['status']]
    ?? ['label' => $post['status'], 'color' => '#6b7280'];
$attachmentUrl = trim((string) ($post['image_url'] ?? ''));
$description = trim((string) $post['description']);
$descriptionExcerpt = departmentDescriptionExcerpt($description);
$priority = departmentPriorityMeta((int) $post['upvotes']);
$postResponses = $responsesByPost[(int) $post['id']] ?? [];

$departmentPostDetails[(int) $post['id']] = [
    'id' => (int) $post['id'],
    'title' => $post['title'],
    'description' => $post['description'],
    'category' => $post['category'] ?: 'Lainnya',
    'statusLabel' => $statusMeta['label'],
    'statusColor' => $statusMeta['color'],
    'author' => $post['author'],
    'nim' => (int) $post['is_anonymous'] === 1 ? '-' : ($post['nim'] ?? '-'),
    'email' => (int) $post['is_anonymous'] === 1 ? '-' : ($post['email'] ?? '-'),
    'createdAt' => $post['created_at'],
    'updatedAt' => $post['updated_at'],
    'upvotes' => (int) $post['upvotes'],
    'downvotes' => (int) $post['downvotes'],
    'comments' => (int) $post['comments_count'],
    'imageUrl' => $attachmentUrl,
    'responses' => $postResponses,
];
?>
<article class="aspiration-card admin-post-card">
    <div class="post-card-top">
        <div class="author-cluster">
            <span class="admin-avatar"><?= e(getInitials($post['author'])); ?></span>
            <div>
                <h2><?= e($post['title']); ?></h2>
                <p><?= e($post['author']); ?> &middot; <?= e($post['category'] ?: 'Lainnya'); ?></p>
            </div>
        </div>
        <span class="admin-status-badge" style="background:<?= e($statusMeta['color']); ?>;">
            <?= e($statusMeta['label']); ?>
        </span>
    </div>

    <p class="post-excerpt"><?= e($descriptionExcerpt); ?></p>

    <div class="post-meta-row">
        <span><?= e(date('d M Y, H:i', strtotime($post['created_at']))); ?></span>
        <span><?= (int) $post['upvotes']; ?> upvote</span>
        <span><?= (int) $post['comments_count']; ?> komentar</span>
        <span class="priority-pill <?= e($priority['class']); ?>"><?= e($priority['label']); ?></span>
    </div>

    <?php if ($attachmentUrl !== ''): ?>
        <div class="attachment-preview">
            <?php if (departmentIsVideo($attachmentUrl)): ?>
                <video src="<?= e($attachmentUrl); ?>" controls preload="metadata"></video>
            <?php else: ?>
                <img src="<?= e($attachmentUrl); ?>" alt="Lampiran aspirasi <?= e($post['title']); ?>" loading="lazy">
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="admin-card-actions" style="grid-template-columns:minmax(0,1fr) auto;">
        <?php if (!in_array($post['status'], ['resolved', 'rejected'], true)): ?>
            <div class="status-form">
                <label for="department-status-<?= (int) $post['id']; ?>">Status</label>
                <select id="department-status-<?= (int) $post['id']; ?>" class="status-select"
                    data-post-id="<?= (int) $post['id']; ?>">
                    <option value="">-- Ubah Status --</option>
                    <option value="in_process" <?= $post['status'] === 'in_process' ? 'selected' : ''; ?>>Diproses</option>
                    <option value="communicated" <?= $post['status'] === 'communicated' ? 'selected' : ''; ?>>
                        Dikomunikasikan
                    </option>
                    <option value="resolved" <?= $post['status'] === 'resolved' ? 'selected' : ''; ?>>Selesai</option>
                </select>
                <input type="text" class="note-input" placeholder="Catatan (opsional)"
                    style="min-height:2.6rem;padding:0 .9rem;border:1px solid #d1d5db;border-radius:.5rem;font:inherit;">
                <button class="save-status-btn" data-post-id="<?= (int) $post['id']; ?>"
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

        <button type="button" class="detail-post-btn detail-button" data-post-id="<?= (int) $post['id']; ?>">
            Detail
        </button>
    </div>

    <?php if ($postResponses): ?>
        <?php $latestResponse = $postResponses[count($postResponses) - 1]; ?>
        <div class="latest-response">
            <strong>Tanggapan admin terbaru</strong>
            <p><?= e($latestResponse['response']); ?></p>
        </div>
    <?php endif; ?>
</article>
