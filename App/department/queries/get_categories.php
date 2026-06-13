<?php

$categories = [];
$result = $conn->query("
    SELECT DISTINCT category
    FROM posts
    WHERE status IN ('in_process', 'communicated', 'resolved')
      AND category IS NOT NULL
      AND category <> ''
    ORDER BY category ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}
