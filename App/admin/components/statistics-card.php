<?php
// ============================================================
// RuangKita - Component Statistics Card
// File: App/admin/components/statistics-card.php
// ============================================================
?>
<div class="stats-grid">
    <?php foreach ([
        ['label' => 'Total Aspirasi', 'value' => $stats['total'],       'color' => '#008891'],
        ['label' => 'Pending',        'value' => $stats['pending'],      'color' => '#f59e0b'],
        ['label' => 'In Progress',    'value' => $stats['in_progress'],  'color' => '#3b82f6'],
        ['label' => 'Completed',      'value' => $stats['completed'],    'color' => '#22c55e'],
        ['label' => 'Rejected',       'value' => $stats['rejected'],     'color' => '#ef4444'],
    ] as $stat): ?>
    <div class="stat-card">
        <div>
            <p><?= $stat['label'] ?></p>
            <strong style="color:<?= $stat['color'] ?>;"><?= $stat['value'] ?></strong>
        </div>
    </div>
    <?php endforeach; ?>
</div>
