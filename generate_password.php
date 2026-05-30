<?php
// ============================================================
// RuangKita - Generate Password Hash
// 
// CARA PAKAI:
//   1. Taruh file ini di folder RuangKita/
//   2. Buka di browser: localhost/RuangKita/generate_password.php
//   3. Copy hash yang muncul ke database_seed.sql
//   4. HAPUS file ini setelah selesai!
// ============================================================

$passwords = [
    'Admin123!'  => password_hash('Admin123!',  PASSWORD_BCRYPT),
    'Dept123!'   => password_hash('Dept123!',   PASSWORD_BCRYPT),
];

foreach ($passwords as $plain => $hash) {
    echo "Password: $plain\n";
    echo "Hash    : $hash\n\n";
}
