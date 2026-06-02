<?php
// ============================================================
// RuangKita - Component Aspiration Card
// File: App/admin/components/aspiration-card.php
// ============================================================

$meta = statusMeta($post['status']);
$authorName = ((int) $post['is_anonymous'] === 1) ? 'Anonymous' : ($post['username'] ?? 'Mahasiswa');
$descriptionLength = function_exists('mb_strlen') ? mb_strlen($post['description']) : strlen($post['description']);
$excerpt = $descriptionLength > 180
    ? (function_exists('mb_substr') ? mb_substr($post['description'], 0, 180) : substr($post['description'], 0, 180)) . '...'
    : $post['description'];
$postResponses = $responsesByPost[(int) $post['id']] ?? [];
$priorityClass = ((int) $post['upvotes'] >= 25) ? 'priority-high' : (((int) $post['upvotes'] >= 10) ? 'priority-medium' : 'priority-normal');
$priorityLabel = ((int) $post['upvotes'] >= 25) ? 'Trending' : (((int) $post['upvotes'] >= 10) ? 'Prioritas' : 'Normal');
$attachmentUrl = (string) ($post['image_url'] ?? '');
$attachmentExtension = strtolower(pathinfo(parse_url($attachmentUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
$isVideoAttachment = in_array($attachmentExtension, ['mp4', 'webm', 'ogg'], true);
$modalData = [
    'id' => (int) $post['id'],
    'author' => $authorName,
    'nim' => ((int) $post['is_anonymous'] === 1) ? '-' : ($post['nim'] ?? '-'),
    'email' => ((int) $post['is_anonymous'] === 1) ? '-' : ($post['email'] ?? '-'),
    'title' => $post['title'],
    'description' => $post['description'],
    'category' => $post['category'] ?: 'Other',
    'status' => $post['status'],
    'statusLabel' => $meta['label'],
    'statusClass' => $meta['class'],
    'createdAt' => $post['created_at'],
    'updatedAt' => $post['updated_at'],
    'upvotes' => (int) $post['upvotes'],
    'downvotes' => (int) $post['downvotes'],
    'comments' => (int) $post['comments_count'],
    'imageUrl' => $attachmentUrl,
    'responses' => array_map(fn ($response) => [
        'admin_name' => $response['admin_name'],
        'response' => $response['response'],
        'created_at' => $response['created_at'],
    ], $postResponses),
];
?>
<article class="admin-post-card" data-post='<?= e(json_encode($modalData)); ?>'>
    <button class="card-open" type="button" aria-label="Lihat detail aspirasi"></button>
    <div class="post-card-top">
        <div class="author-cluster">
            <span class="admin-avatar"><?= e(getInitials($authorName)); ?></span>
            <div>
                <h2><?= e($post['title']); ?></h2>
                <p><?= e($authorName); ?> · <?= e($post['category'] ?: 'Other'); ?></p>
            </div>
        </div>
        <span class="admin-status-badge <?= e($meta['class']); ?>" data-status-badge="<?= (int) $post['id']; ?>">
            <?= e($meta['label']); ?>
        </span>
    </div>

    <p class="post-excerpt"><?= e($excerpt); ?></p>

    <div class="post-meta-row">
        <span><?= e(date('d M Y, H:i', strtotime($post['created_at']))); ?></span>
        <span><?= (int) $post['upvotes']; ?> upvote</span>
        <span><?= (int) $post['comments_count']; ?> komentar</span>
        <span class="priority-pill <?= e($priorityClass); ?>"><?= e($priorityLabel); ?></span>
    </div>

    <?php if ($attachmentUrl !== ''): ?>
        <div class="attachment-preview">
            <?php if ($isVideoAttachment): ?>
                <video src="<?= e($attachmentUrl); ?>" muted controls></video>
            <?php else: ?>
                <img src="<?= e($attachmentUrl); ?>" alt="Lampiran aspirasi">
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="admin-card-actions">
        <form class="status-form" data-post-id="<?= (int) $post['id']; ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="post_id" value="<?= (int) $post['id']; ?>">
            <label for="status-<?= (int) $post['id']; ?>">Status</label>
            <select id="status-<?= (int) $post['id']; ?>" name="status">
                <?php foreach ($statusOptions as $value => $label): ?>
                    <?php $selected = ($post['status'] === $value); ?>
                    <option value="<?= e($value); ?>" <?= $selected ? 'selected' : ''; ?>>
                        <?= e($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <button class="detail-button" type="button">Detail</button>
    </div>

    <?php if (!empty($post['latest_response'])): ?>
        <div class="latest-response">
            <strong>Respons terbaru</strong>
            <p><?= e($post['latest_response']); ?></p>
        </div>
    <?php endif; ?>

    <form class="response-form" data-post-id="<?= (int) $post['id']; ?>">
        <input type="hidden" name="action" value="add_response">
        <input type="hidden" name="post_id" value="<?= (int) $post['id']; ?>">
        <label for="response-<?= (int) $post['id']; ?>">Tanggapan resmi</label>
        <textarea id="response-<?= (int) $post['id']; ?>" name="response" rows="3" placeholder="Tulis tanggapan resmi untuk aspirasi ini"></textarea>
        <button type="submit">Kirim Tanggapan</button>
    </form>
</article>
