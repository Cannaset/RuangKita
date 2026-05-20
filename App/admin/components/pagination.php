<?php
// ============================================================
// RuangKita - Component Pagination
// File: App/admin/components/pagination.php
// ============================================================
?>
<?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Pagination aspirasi">
        <?php if ($page > 1): ?>
            <?php $prevQuery = http_build_query(array_merge($queryBase, ['page' => $page - 1])); ?>
            <a href="?<?= e($prevQuery); ?>">Sebelumnya</a>
        <?php endif; ?>

        <span>Halaman <?= $page; ?> dari <?= $totalPages; ?></span>

        <?php if ($page < $totalPages): ?>
            <?php $nextQuery = http_build_query(array_merge($queryBase, ['page' => $page + 1])); ?>
            <a href="?<?= e($nextQuery); ?>">Berikutnya</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
