<?php
// ============================================================
// RuangKita - Component Statistics Card
// File: App/admin/components/statistics-card.php
// ============================================================
?>
<section class="stats-grid" aria-label="Ringkasan aspirasi">
    <article class="stat-card stat-total">
        <img src="../image/total.png" alt="Total" class="stat-icon-img">
        <div>
            <p>Total Aspirasi</p>
            <strong><?= $stats['total']; ?></strong>
        </div>
    </article>

    <article class="stat-card stat-pending">
        <img src="../image/pending.png" alt="Pending" class="stat-icon-img">
        <div>
            <p>Pending</p>
            <strong><?= $stats['pending']; ?></strong>
        </div>
    </article>

    <article class="stat-card stat-progress">
        <img src="../image/progress.png" alt="In Progress" class="stat-icon-img">
        <div>
            <p>In Progress</p>
            <strong><?= $stats['in_progress']; ?></strong>
        </div>
    </article>

    <article class="stat-card stat-completed">
        <img src="../image/completed.png" alt="Completed" class="stat-icon-img">
        <div>
            <p>Completed</p>
            <strong><?= $stats['completed']; ?></strong>
        </div>
    </article>

    <article class="stat-card stat-rejected">
        <img src="../image/rejected.png" alt="Rejected" class="stat-icon-img">
        <div>
            <p>Rejected</p>
            <strong><?= $stats['rejected']; ?></strong>
        </div>
    </article>
</section>
