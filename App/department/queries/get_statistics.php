<?php

$statistics = [
    'total' => 0,
    'in_process' => 0,
    'communicated' => 0,
    'resolved' => 0,
];

$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'in_process') AS in_process,
        SUM(status = 'communicated') AS communicated,
        SUM(status = 'resolved') AS resolved
    FROM posts
    WHERE status IN ('in_process', 'communicated', 'resolved')
");

if ($result) {
    $row = $result->fetch_assoc();
    foreach ($statistics as $key => $value) {
        $statistics[$key] = (int) ($row[$key] ?? 0);
    }
}
