<?php
// Endpoint: POST /api/release.php
// Body JSON: { "username": "...", "license_key": "...", "hardware_id": "..." }
//
// Self-release: HANYA bisa dilepas dari komputer yang sama tempat
// lisensi itu diaktifkan (hardware_id harus cocok). Ini untuk kasus
// "saya mau pindah lisensi dari komputer A ke komputer B" — jalankan
// tombol lepas ini DI KOMPUTER A dulu.
//
// Kalau komputer A sudah rusak/hilang dan tidak bisa diakses lagi,
// gunakan Admin Panel (server/admin) untuk memaksa lepas lisensi.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
}

$in = json_input();
$username   = trim($in['username'] ?? '');
$licenseKey = trim($in['license_key'] ?? '');
$hardwareId = trim($in['hardware_id'] ?? '');

if ($username === '' || $licenseKey === '' || $hardwareId === '') {
    json_response(['ok' => false, 'message' => 'Data tidak lengkap.'], 400);
}

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT l.id AS license_id, a.hardware_id
     FROM licenses l
     JOIN users u ON u.id = l.user_id
     LEFT JOIN activations a ON a.license_id = l.id
     WHERE l.license_key = ? AND u.username = ?'
);
$stmt->execute([$licenseKey, $username]);
$row = $stmt->fetch();

if (!$row) {
    json_response(['ok' => false, 'message' => 'Username atau license key salah.'], 404);
}

if (!$row['hardware_id']) {
    json_response(['ok' => true, 'message' => 'Lisensi memang belum aktif di komputer manapun.']);
}

if ($row['hardware_id'] !== $hardwareId) {
    json_response(['ok' => false, 'message' => 'Lisensi ini aktif di komputer lain, tidak bisa dilepas dari sini.'], 409);
}

$stmt = $pdo->prepare('DELETE FROM activations WHERE license_id = ?');
$stmt->execute([$row['license_id']]);

json_response(['ok' => true, 'message' => 'Lisensi berhasil dilepas dari komputer ini.']);
