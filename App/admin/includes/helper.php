<?php

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function tableExists(mysqli $conn, string $tableName): bool
{
    $stmt = $conn->prepare("
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $tableName);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function ensureAdminSchema(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS posts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id INT UNSIGNED NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT NOT NULL,
            category VARCHAR(100) DEFAULT NULL,
            image_url VARCHAR(255) DEFAULT NULL,
            is_anonymous TINYINT(1) DEFAULT 0,
            status ENUM('not_reviewed', 'in_process', 'communicated', 'resolved', 'rejected') DEFAULT 'not_reviewed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_posts_student_id (student_id),
            INDEX idx_posts_status (status),
            INDEX idx_posts_category (category),
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS votes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            student_id INT UNSIGNED NOT NULL,
            vote_type ENUM('upvote', 'downvote') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vote (post_id, student_id),
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS comments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            student_id INT UNSIGNED NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_comments_post_id (post_id),
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS post_status_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            changed_by_role ENUM('admin', 'department') NOT NULL,
            changed_by_id INT UNSIGNED NOT NULL,
            old_status ENUM('not_reviewed', 'in_process', 'communicated', 'resolved', 'rejected') DEFAULT NULL,
            new_status ENUM('not_reviewed', 'in_process', 'communicated', 'resolved', 'rejected') NOT NULL,
            note TEXT DEFAULT NULL,
            changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_post_status_logs_post_id (post_id),
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS admin_responses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            admin_id INT UNSIGNED NOT NULL,
            response TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_admin_responses_post_id (post_id),
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        )
    ");

    $dbNameResult = $conn->query('SELECT DATABASE() AS db_name');
    $dbNameRow = $dbNameResult ? $dbNameResult->fetch_assoc() : null;
    $dbName = $dbNameRow['db_name'] ?? '';

    if ($dbName !== '') {
        $stmt = $conn->prepare("
            SELECT COLUMN_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = 'posts'
              AND COLUMN_NAME = 'status'
            LIMIT 1
        ");
        $stmt->bind_param('s', $dbName);
        $stmt->execute();
        $column = $stmt->get_result()->fetch_assoc();

        if ($column && strpos($column['COLUMN_TYPE'], "'rejected'") === false) {
            $conn->query("
                ALTER TABLE posts
                MODIFY status ENUM('not_reviewed', 'in_process', 'communicated', 'resolved', 'rejected')
                DEFAULT 'not_reviewed'
            ");
        }

        $stmt = $conn->prepare("
            SELECT COLUMN_NAME, COLUMN_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = 'post_status_logs'
              AND COLUMN_NAME IN ('old_status', 'new_status')
        ");
        $stmt->bind_param('s', $dbName);
        $stmt->execute();
        $logColumns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($logColumns as $logColumn) {
            if (strpos($logColumn['COLUMN_TYPE'], "'rejected'") === false) {
                $columnName = $logColumn['COLUMN_NAME'];
                $nullable = $columnName === 'old_status' ? 'DEFAULT NULL' : 'NOT NULL';
                $conn->query("
                    ALTER TABLE post_status_logs
                    MODIFY $columnName ENUM('not_reviewed', 'in_process', 'communicated', 'resolved', 'rejected')
                    $nullable
                ");
            }
        }
    }
}

function bindParams(mysqli_stmt $stmt, string $types, array &$params): void
{
    if ($types === '') {
        return;
    }

    $refs = [];
    foreach ($params as $key => &$value) {
        $refs[$key] = &$value;
    }

    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

function respondJson(int $code, array $data): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function statusMeta(string $status): array
{
    return match ($status) {
        'in_process', 'communicated' => ['label' => 'In Progress', 'class' => 'status-in-progress'],
        'resolved' => ['label' => 'Completed', 'class' => 'status-completed'],
        'rejected' => ['label' => 'Rejected', 'class' => 'status-rejected'],
        default => ['label' => 'Pending', 'class' => 'status-pending'],
    };
}

function normalizeStatusFilter(string $status): array
{
    return match ($status) {
        'in_process' => ['in_process', 'communicated'],
        'resolved' => ['resolved'],
        'rejected' => ['rejected'],
        'not_reviewed' => ['not_reviewed'],
        default => [],
    };
}

function getInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $first = $parts[0][0] ?? 'A';
    $second = isset($parts[1]) ? $parts[1][0] : '';

    return strtoupper($first . $second);
}
