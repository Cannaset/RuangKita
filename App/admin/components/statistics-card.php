<?php
// ============================================================
// RuangKita - Component Statistics Card
// File: App/admin/components/statistics-card.php
// ============================================================
?>
<div class="stats-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem;">
    <?php foreach ([
        ['label' => 'Total Aspirasi', 'value' => $stats['total'],       'color' => '#008891'],
        ['label' => 'Pending',        'value' => $stats['pending'],      'color' => '#f59e0b'],
        ['label' => 'In Progress',    'value' => $stats['in_progress'],  'color' => '#3b82f6'],
        ['label' => 'Completed',      'value' => $stats['completed'],    'color' => '#22c55e'],
        ['label' => 'Rejected',       'value' => $stats['rejected'],     'color' => '#ef4444'],
    ] as $stat): ?>
    <div style="background:white;border-radius:.75rem;padding:1.25rem 1.5rem;border:1px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,.05);">
        <p style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin:0 0 .5rem;"><?= $stat['label'] ?></p>
        <p style="font-size:2rem;font-weight:800;color:<?= $stat['color'] ?>;margin:0;"><?= $stat['value'] ?></p>
    </div>
    <?php endforeach; ?>
</div>