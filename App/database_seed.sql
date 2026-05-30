-- ============================================================
-- RuangKita - Seed Data Admin & Department
-- Jalankan file ini SETELAH ruangkita.sql sudah dijalankan
-- ============================================================

USE ruangkita;

-- ============================================================
-- LANGKAH 1: Generate hash password dulu
-- Buka generate_password.php di browser, copy hasilnya ke sini
-- ============================================================

-- INSERT ADMIN
-- Email    : admin@ruangkita.ac.id
-- Password : Admin123!  (ganti hash di bawah setelah generate)
INSERT INTO admins (username, email, password) VALUES
(
    'Admin RuangKita',
    'admin@ruangkita.ac.id',
    '$2y$10$8/2.kX.amN8.Qn8j8nog5u3flQOfwy.R.ZbFiUOrhuD/X0DEeQ3ua'
);

-- INSERT DEPARTMENTS
-- Password default semua: Dept123!
INSERT INTO departments (name, username, email, password) VALUES
(
    'Bagian Akademik',
    'dept_akademik',
    'akademik@ruangkita.ac.id',
    'GANTI_DENGAN_HASH_DARI_generate_password.php'
),
(
    'Bagian Fasilitas',
    'dept_fasilitas',
    'fasilitas@ruangkita.ac.id',
    'GANTI_DENGAN_HASH_DARI_generate_password.php'
),
(
    'Bagian Kebersihan',
    'dept_kebersihan',
    'kebersihan@ruangkita.ac.id',
    'GANTI_DENGAN_HASH_DARI_generate_password.php'
);
