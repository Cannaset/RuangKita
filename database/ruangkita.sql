CREATE DATABASE IF NOT EXISTS ruangkita;

USE ruangkita;

CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    nim VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 1. ADMINS
--    Akun untuk admin / BEM yang moderasi postingan
-- ============================================================
CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. DEPARTMENTS
--    Akun untuk pihak departemen / fakultas yang menangani laporan
-- ============================================================
CREATE TABLE IF NOT EXISTS departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,  
    username VARCHAR(150) NOT NULL,       -- nama departemen, misal "Bagian Akademik"
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- CONTOH AKUN MANUAL
-- Admin dan department pada sistem ini login dengan password plain text.
-- Password contoh admin: admin123
-- Password contoh department: department123
-- ============================================================
INSERT INTO admins (username, email, password)
VALUES
    ('Admin RuangKita', 'admin@ruangkita.local', 'admin123')
ON DUPLICATE KEY UPDATE
    username = VALUES(username),
    password = VALUES(password);

INSERT INTO departments (name, username, email, password)
VALUES
    ('Bagian Akademik', 'Bagian Akademik', 'akademik@ruangkita.local', 'department123')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    username = VALUES(username),
    password = VALUES(password);

-- ============================================================
-- 3. POSTS
--    Isi laporan / aspirasi yang dikirim mahasiswa
-- ============================================================
CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,           -- siapa yang posting
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) DEFAULT NULL,         -- misal: Fasilitas, Akademik, dll
    image_url VARCHAR(255) DEFAULT NULL,        -- opsional, path ke gambar
    is_anonymous TINYINT(1) DEFAULT 0,          -- 0 = tidak anonim, 1 = anonim
    status ENUM(
        'not_reviewed',
        'in_process',
        'communicated',
        'resolved',
        'rejected'
    ) DEFAULT 'not_reviewed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- 4. VOTES
--    Upvote / downvote mahasiswa terhadap sebuah postingan
--    Satu mahasiswa hanya boleh 1 vote per postingan
-- ============================================================
CREATE TABLE IF NOT EXISTS votes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    vote_type ENUM('upvote', 'downvote') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_vote (post_id, student_id),   -- cegah double vote
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- 5. COMMENTS
--    Komentar pada sebuah postingan
-- ============================================================
CREATE TABLE IF NOT EXISTS comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- 6. POST_STATUS_LOGS
--    Rekam jejak perubahan status postingan
--    Siapa yang ubah, kapan, dari status apa ke apa
-- ============================================================
CREATE TABLE IF NOT EXISTS post_status_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    changed_by_role ENUM('admin', 'department') NOT NULL,   -- siapa yang ubah
    changed_by_id INT UNSIGNED NOT NULL,                    -- id admin atau department
    old_status ENUM(
        'not_reviewed',
        'in_process',
        'communicated',
        'resolved',
        'rejected'
    ) DEFAULT NULL,
    new_status ENUM(
        'not_reviewed',
        'in_process',
        'communicated',
        'resolved',
        'rejected'
    ) NOT NULL,
    note TEXT DEFAULT NULL,                                 -- catatan opsional dari admin
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- ============================================================
-- 7. ADMIN_RESPONSES
--    Tanggapan resmi admin terhadap aspirasi mahasiswa
-- ============================================================
CREATE TABLE IF NOT EXISTS admin_responses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED NOT NULL,
    response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_admin_responses_post_id (post_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);
