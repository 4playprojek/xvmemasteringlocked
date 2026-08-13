<?php
// Endpoint: POST /api/activate.php
// Body JSON: { "username": "...", "license_key": "...", "hardware_id": "...", "computer_name": "..." }
//
// Mengunci lisensi ke satu komputer (hardware_id). Kalau lisensi sudah
// aktif di komputer lain -> ditolak, sampai dilepas dari komputer itu
// (self-release) atau dilepas paksa oleh admin.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
}

$in = json_input();
$username    = trim($in['username'] ?? '');
$licenseKey  = trim($in['license_key'] ?? '');
$hardwareId  = trim($in['hardware_id'] ?? '');
$computer    = trim($in['computer_name'] ?? '');

if ($username === '' || $licenseKey === '' || $hardwareId === '') {
    json_response(['ok' => false, 'message' => 'Username, license key, dan hardware id wajib diisi.'], 400);
}

$pdo = db();

$stmt = $pdo->prepare(
    'SELECT l.id AS license_id, l.status, u.username
     FROM licenses l
     JOIN users u ON u.id = l.user_id
     WHERE l.license_key = ? AND u.username = ?'
);
$stmt->execute([$licenseKey, $username]);
$license = $stmt->fetch();

if (!$license) {
    json_response(['ok' => false, 'message' => 'Username atau license key salah.'], 404);
}

if ($license['status'] !== 'active') {
    json_response(['ok' => false, 'message' => 'License ini sudah dinonaktifkan (revoked).'], 403);
}

// Cek apakah sudah ada aktivasi untuk license ini
$stmt = $pdo->prepare('SELECT hardware_id FROM activations WHERE license_id = ?');
$stmt->execute([$license['license_id']]);
$existing = $stmt->fetch();

if ($existing) {
    if ($existing['hardware_id'] === $hardwareId) {
        // Sudah aktif di komputer yang sama -> perbarui last_check_at, ok
        $stmt = $pdo->prepare('UPDATE activations SET last_check_at = NOW(), computer_name = ?, ip_address = ? WHERE license_id = ?');
        $stmt->execute([$computer, $_SERVER['REMOTE_ADDR'] ?? '', $license['license_id']]);
        json_response(['ok' => true, 'message' => 'Lisensi aktif di komputer ini.']);
    } else {
        // Sudah dipakai komputer lain
        json_response([
            'ok' => false,
            'message' => 'License ini sedang digunakan di komputer lain. Lepas dulu lisensi dari komputer tersebut sebelum memakainya di sini.'
        ], 409);
    }
}

// Belum ada aktivasi -> buat baru, kunci ke komputer ini
$stmt = $pdo->prepare(
    'INSERT INTO activations (license_id, hardware_id, computer_name, ip_address, activated_at, last_check_at)
     VALUES (?, ?, ?, ?, NOW(), NOW())'
);
$stmt->execute([$license['license_id'], $hardwareId, $computer, $_SERVER['REMOTE_ADDR'] ?? '']);

json_response(['ok' => true, 'message' => 'Lisensi berhasil diaktifkan di komputer ini.']);
