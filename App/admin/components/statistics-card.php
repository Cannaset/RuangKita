<?php
// ============================================================
// RuangKita - Component Statistics Card
// File: App/admin/components/statistics-card.php
// ============================================================
?>
<section class="stats-grid" aria-label="Ringkasan aspirasi">
    <article class="stat-card stat-total">
        <span class="stat-icon">T</span>
        <div>
            <p>Total Aspirasi</p>
            <strong><?= $stats['total']; ?></strong>
        </div>
    </article>
    <article class="stat-card stat-pending">
        <span class="stat-icon">P</span>
        <div>
            <p>Pending</p>
            <strong><?= $stats['pending']; ?></strong>
        </div>
    </article>
    <article class="stat-card stat-progress">
        <span class="stat-icon">I</span>
        <div>
            <p>In Progress</p>
            <strong><?= $stats['in_progress']; ?></strong>
        </div>
    </article>
    <article class="stat-card stat-completed">
        <span class="stat-icon">C</span>
        <div>
            <p>Completed</p>
            <strong><?= $stats['completed']; ?></strong>
        </div>
    </article>
    <article class="stat-card stat-rejected">
        <span class="stat-icon">R</span>
        <div>
            <p>Rejected</p>
            <strong><?= $stats['rejected']; ?></strong>
        </div>
    </article>
</section>
