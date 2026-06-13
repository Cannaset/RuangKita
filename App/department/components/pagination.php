<?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Pagination aspirasi">
        <?php if ($page > 1): ?>
            <a href="?<?= e(http_build_query(array_merge($queryBase, ['page' => $page - 1]))); ?>">Sebelumnya</a>
        <?php endif; ?>

        <span>Halaman <?= $page; ?> dari <?= $totalPages; ?></span>

        <?php if ($page < $totalPages): ?>
            <a href="?<?= e(http_build_query(array_merge($queryBase, ['page' => $page + 1]))); ?>">Berikutnya</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
