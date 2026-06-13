<?php

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function getInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $first = strtoupper($parts[0][0] ?? 'U');
    $second = isset($parts[1]) ? strtoupper($parts[1][0]) : '';

    return $first . $second;
}

function departmentStatusLabels(): array
{
    return [
        'not_reviewed' => ['label' => 'Belum Ditinjau', 'color' => '#f59e0b'],
        'in_process' => ['label' => 'Diproses', 'color' => '#3b82f6'],
        'communicated' => ['label' => 'Dikomunikasikan', 'color' => '#8b5cf6'],
        'resolved' => ['label' => 'Selesai', 'color' => '#22c55e'],
        'rejected' => ['label' => 'Ditolak', 'color' => '#ef4444'],
    ];
}

function departmentDescriptionExcerpt(string $description, int $limit = 180): string
{
    $length = function_exists('mb_strlen') ? mb_strlen($description) : strlen($description);
    if ($length <= $limit) {
        return $description;
    }

    $excerpt = function_exists('mb_substr')
        ? mb_substr($description, 0, $limit)
        : substr($description, 0, $limit);

    return $excerpt . '...';
}

function departmentPriorityMeta(int $upvotes): array
{
    if ($upvotes >= 25) {
        return ['class' => 'priority-high', 'label' => 'Trending'];
    }

    if ($upvotes >= 10) {
        return ['class' => 'priority-medium', 'label' => 'Prioritas'];
    }

    return ['class' => 'priority-normal', 'label' => 'Normal'];
}

function departmentIsVideo(string $url): bool
{
    $path = parse_url($url, PHP_URL_PATH) ?: '';
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    return in_array($extension, ['mp4', 'webm', 'ogg'], true);
}
