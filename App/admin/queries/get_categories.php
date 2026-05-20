<?php
// ============================================================
// RuangKita - Query Categories
// File: App/admin/queries/get_categories.php
// ============================================================

$categoryResult = $conn->query("
    SELECT DISTINCT category
    FROM posts
    WHERE category IS NOT NULL AND category <> ''
    ORDER BY category ASC
");
$categories = [];
if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}
