<?php
// ==========================================================
// KONFIGURASI - isi sesuai kredensial hosting Anda
// (cPanel > MySQL Databases > buat database & user, lalu isi di sini)
// ==========================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'ISI_NAMA_DATABASE');
define('DB_USER', 'ISI_USER_DATABASE');
define('DB_PASS', 'ISI_PASSWORD_DATABASE');

// Username & password login admin panel.
// PASSWORD DEFAULT di bawah ini adalah "admin123" — GANTI SEGERA!
// Cara generate hash baru (jalankan di terminal hosting / local PHP):
//   php -r "echo password_hash('password_baru_anda', PASSWORD_DEFAULT);"
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2b$10$VSJkfuGGNwWEGJ3u4Q2dHu7xmFERQyWaJtcuZtBvqt.oH6cBX.U5u');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
