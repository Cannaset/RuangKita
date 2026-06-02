<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// --- Helper: kirim response JSON dan stop ---
function respond(int $code, array $data): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// --- Helper: hitung waktu relatif (misal "2 jam yang lalu") ---
function timeAgo(string $datetime): string
{
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0)
        return $diff->y . ' tahun yang lalu';
    if ($diff->m > 0)
        return $diff->m . ' bulan yang lalu';
    if ($diff->d > 0)
        return $diff->d . ' hari yang lalu';
    if ($diff->h > 0)
        return $diff->h . ' jam yang lalu';
    if ($diff->i > 0)
        return $diff->i . ' menit yang lalu';
    return 'Baru saja';
}

// --- Helper: ambil inisial dari nama ---
function getInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $first = $parts[0][0] ?? 'U';
    $second = isset($parts[1]) ? $parts[1][0] : '';
    return strtoupper($first . $second);
}

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// GET - Ambil post untuk feed, atau 1 post by ID
//
// Query params opsional:
//   ?id=5            → ambil 1 post spesifik
//   ?category=Facilities
//   ?sort=newest | popular | unresolved
//   ?search=kata kunci
//   ?page=1          → pagination (10 post per halaman)
// ============================================================
if ($method === 'GET') {

    // --- Ambil 1 post by ID ---
    if (!empty($_GET['id'])) {
        $post_id = (int) $_GET['id'];
        $student_id = (int) ($_SESSION['student']['id'] ?? 0);

        $stmt = $conn->prepare("
            SELECT
                p.id,
                p.title,
                p.description,
                p.category,
                p.image_url,
                p.is_anonymous,
                p.status,
                p.created_at,
                s.username,
                s.nim,
                COALESCE(vt.upvotes,   0) AS upvotes,
                COALESCE(vt.downvotes, 0) AS downvotes,
                COALESCE(ct.total,     0) AS comments_count,
                mv.vote_type                AS my_vote
            FROM posts p
            LEFT JOIN students s ON s.id = p.student_id
            LEFT JOIN (
                SELECT post_id,
                    SUM(vote_type = 'upvote')   AS upvotes,
                    SUM(vote_type = 'downvote') AS downvotes
                FROM votes GROUP BY post_id
            ) vt ON vt.post_id = p.id
            LEFT JOIN (
                SELECT post_id, COUNT(*) AS total
                FROM comments GROUP BY post_id
            ) ct ON ct.post_id = p.id
            LEFT JOIN votes mv
                ON mv.post_id = p.id AND mv.student_id = ?
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->bind_param('ii', $student_id, $post_id);
        $stmt->execute();
        $post = $stmt->get_result()->fetch_assoc();

        if (!$post) {
            respond(404, ['success' => false, 'message' => 'Post tidak ditemukan.']);
        }

        // Sembunyikan identitas kalau anonim
        if ($post['is_anonymous']) {
            $post['username'] = 'Anonim';
            $post['nim'] = null;
        }

        $post['initials'] = getInitials($post['username']);
        $post['time_ago'] = timeAgo($post['created_at']);

        respond(200, ['success' => true, 'data' => $post]);
    }

    // --- Ambil semua post untuk feed ---
    $student_id = (int) ($_SESSION['student']['id'] ?? 0);

    // Ambil query params
    $category = trim($_GET['category'] ?? '');
    $sort = trim($_GET['sort'] ?? 'newest');
    $search = trim($_GET['search'] ?? '');
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $per_page = 10;
    $offset = ($page - 1) * $per_page;

    // Bangun WHERE clause
    $where = ["p.status != 'rejected'"]; // post yang rejected tidak muncul di feed
    $types = '';
    $params = [];

    if ($category !== '' && $category !== 'All') {
        $where[] = 'p.category = ?';
        $types .= 's';
        $params[] = $category;
    }

    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = '(p.title LIKE ? OR p.description LIKE ?)';
        $types .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }

    // Filter status "unresolved"
    if ($sort === 'unresolved') {
        $where[] = "p.status NOT IN ('resolved', 'rejected')";
    }

    $where_sql = 'WHERE ' . implode(' AND ', $where);

    // ORDER BY
    $order_sql = match ($sort) {
        'popular' => 'COALESCE(vt.upvotes, 0) DESC, p.created_at DESC',
        'unresolved' => 'p.created_at DESC',
        default => 'p.created_at DESC', // newest
    };

    // Hitung total untuk pagination
    $count_types = $types;
    $count_params = $params;
    $count_sql = "SELECT COUNT(*) AS total FROM posts p $where_sql";
    $count_stmt = $conn->prepare($count_sql);
    if ($count_types !== '') {
        $refs = [&$count_types];
        foreach ($count_params as &$val) {
            $refs[] = &$val;
        }
        call_user_func_array([$count_stmt, 'bind_param'], $refs);
    }
    $count_stmt->execute();
    $total_rows = (int) ($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $total_pages = max(1, (int) ceil($total_rows / $per_page));

    // Query utama
    $list_sql = "
        SELECT
            p.id,
            p.title,
            p.description,
            p.category,
            p.image_url,
            p.is_anonymous,
            p.status,
            p.created_at,
            s.username,
            COALESCE(vt.upvotes,   0) AS upvotes,
            COALESCE(vt.downvotes, 0) AS downvotes,
            COALESCE(ct.total,     0) AS comments_count,
            mv.vote_type                AS my_vote
        FROM posts p
        LEFT JOIN students s ON s.id = p.student_id
        LEFT JOIN (
            SELECT post_id,
                SUM(vote_type = 'upvote')   AS upvotes,
                SUM(vote_type = 'downvote') AS downvotes
            FROM votes GROUP BY post_id
        ) vt ON vt.post_id = p.id
        LEFT JOIN (
            SELECT post_id, COUNT(*) AS total
            FROM comments GROUP BY post_id
        ) ct ON ct.post_id = p.id
        LEFT JOIN votes mv
            ON mv.post_id = p.id AND mv.student_id = ?
        $where_sql
        ORDER BY $order_sql
        LIMIT ? OFFSET ?
    ";

    $list_stmt = $conn->prepare($list_sql);
    $list_types = 'i' . $types . 'ii';
    $list_params = array_merge([$student_id], $params, [$per_page, $offset]);

    $refs = [&$list_types];
    foreach ($list_params as &$val) {
        $refs[] = &$val;
    }
    call_user_func_array([$list_stmt, 'bind_param'], $refs);

    $list_stmt->execute();
    $posts = $list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Tambah field bantu untuk frontend
    foreach ($posts as &$post) {
        if ($post['is_anonymous']) {
            $post['username'] = 'Anonim';
        }

        $post['initials'] = getInitials($post['username']);
        $post['time_ago'] = timeAgo($post['created_at']);

        // mapping untuk frontend
        $post['author'] = $post['username'];
        $post['timestamp'] = $post['time_ago'];
        $post['content'] = $post['description'];
        $post['comments'] = $post['comments_count'];
        $post['imageUrl'] = $post['image_url'];
        $post['hasImage'] = !empty($post['image_url']);
    }

    respond(200, [
        'success' => true,
        'data' => $posts,
        'pagination' => [
            'page' => $page,
            'per_page' => $per_page,
            'total_rows' => $total_rows,
            'total_pages' => $total_pages,
        ],
    ]);
}

// ============================================================
// POST - Buat post baru
// Menerima multipart/form-data (karena ada upload gambar)
//
// Field yang dibutuhkan:
//   title       (wajib, maks 200 karakter)
//   content     (wajib, maks 2000 karakter)   ← nama field di form create-post.php
//   category    (wajib: Facilities/Academic/Cleanliness/Other)
//   anonymous   (opsional, nilai "on" atau "1")
//   image       (opsional, file gambar/video)
// ============================================================
if ($method === 'POST') {

    // Harus login dulu
    $student = $_SESSION['student'] ?? null;
    if (!$student) {
        respond(401, ['success' => false, 'message' => 'Kamu harus login dulu.']);
    }

    $student_id = (int) $student['id'];

    // Ambil dan validasi input
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['content'] ?? ''); // nama field di form adalah "content"
    $category = trim($_POST['category'] ?? '');
    $anonymous = !empty($_POST['anonymous']) ? 1 : 0;

    $valid_categories = ['Facilities', 'Academic', 'Cleanliness', 'Other'];

    if ($title === '') {
        respond(400, ['success' => false, 'message' => 'Judul tidak boleh kosong.']);
    }
    if (strlen($title) > 200) {
        respond(400, ['success' => false, 'message' => 'Judul maksimal 200 karakter.']);
    }
    if ($description === '') {
        respond(400, ['success' => false, 'message' => 'Deskripsi tidak boleh kosong.']);
    }
    if (strlen($description) > 2000) {
        respond(400, ['success' => false, 'message' => 'Deskripsi maksimal 2000 karakter.']);
    }
    if (!in_array($category, $valid_categories)) {
        respond(400, ['success' => false, 'message' => 'Kategori tidak valid.']);
    }

    // --- Handle upload gambar (opsional) ---
    $image_url = null;

    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $max_size = 10 * 1024 * 1024; // 10MB
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];

        if ($file['size'] > $max_size) {
            respond(400, ['success' => false, 'message' => 'File terlalu besar. Maksimal 10MB.']);
        }

        // Validasi tipe file dari konten aslinya (bukan hanya ekstensi)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed)) {
            respond(400, ['success' => false, 'message' => 'Tipe file tidak didukung. Gunakan JPG, PNG, GIF, atau MP4.']);
        }

        // Simpan ke folder uploads
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'post_' . $student_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $upload_dir = __DIR__ . '/../image/uploads/';

        // Buat folder kalau belum ada
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $dest = $upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            respond(500, ['success' => false, 'message' => 'Gagal menyimpan file.']);
        }

        $image_url = '../image/uploads/' . $filename;
    }

    // --- Simpan post ke database ---
    $stmt = $conn->prepare("
        INSERT INTO posts (student_id, title, description, category, image_url, is_anonymous, status)
        VALUES (?, ?, ?, ?, ?, ?, 'not_reviewed')
    ");
    $stmt->bind_param('issssi', $student_id, $title, $description, $category, $image_url, $anonymous);

    if (!$stmt->execute()) {
        respond(500, ['success' => false, 'message' => 'Gagal menyimpan post ke database.']);
    }

    $new_post_id = $conn->insert_id;

    respond(201, [
        'success' => true,
        'message' => 'Aspirasi berhasil dikirim!',
        'data' => [
            'id' => $new_post_id,
            'title' => $title,
            'category' => $category,
            'status' => 'not_reviewed',
        ],
    ]);
}

// Kalau method selain GET/POST
respond(405, ['success' => false, 'message' => 'Method tidak diizinkan.']);