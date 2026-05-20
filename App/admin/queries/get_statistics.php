<?php
// ============================================================
// RuangKita - Query Statistics
// File: App/admin/queries/get_statistics.php
// ============================================================

$statsResult = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'not_reviewed') AS pending,
        SUM(status IN ('in_process', 'communicated')) AS in_progress,
        SUM(status = 'resolved') AS completed,
        SUM(status = 'rejected') AS rejected
    FROM posts
");
$stats = $statsResult ? $statsResult->fetch_assoc() : [];
$stats = [
    'total' => (int) ($stats['total'] ?? 0),
    'pending' => (int) ($stats['pending'] ?? 0),
    'in_progress' => (int) ($stats['in_progress'] ?? 0),
    'completed' => (int) ($stats['completed'] ?? 0),
    'rejected' => (int) ($stats['rejected'] ?? 0),
];
