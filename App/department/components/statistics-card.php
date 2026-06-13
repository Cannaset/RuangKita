<?php
$statCards = [
    ['label' => 'Total Disetujui', 'value' => $statistics['total'], 'color' => '#008891'],
    ['label' => 'Diproses', 'value' => $statistics['in_process'], 'color' => '#3b82f6'],
    ['label' => 'Dikomunikasikan', 'value' => $statistics['communicated'], 'color' => '#8b5cf6'],
    ['label' => 'Selesai', 'value' => $statistics['resolved'], 'color' => '#22c55e'],
];
?>
<div class="stats-grid">
    <?php foreach ($statCards as $stat): ?>
        <div class="stat-card">
            <div>
                <p><?= e($stat['label']); ?></p>
                <strong style="color:<?= e($stat['color']); ?>;"><?= (int) $stat['value']; ?></strong>
            </div>
        </div>
    <?php endforeach; ?>
</div>
