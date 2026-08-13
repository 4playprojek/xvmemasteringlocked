<?php
// Endpoint: POST /api/validate.php
// Body JSON: { "username": "...", "license_key": "...", "hardware_id": "..." }
//
// Dipanggil setiap kali aplikasi dibuka, untuk memastikan lisensi masih
// aktif dan masih terkunci ke komputer ini (belum di-revoke / dilepas
// admin / dipindah ke komputer lain).

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
    'SELECT l.id AS license_id, l.status, a.hardware_id
     FROM licenses l
     JOIN users u ON u.id = l.user_id
     LEFT JOIN activations a ON a.license_id = l.id
     WHERE l.license_key = ? AND u.username = ?'
);
$stmt->execute([$licenseKey, $username]);
$row = $stmt->fetch();

if (!$row || $row['status'] !== 'active') {
    json_response(['ok' => false, 'message' => 'Lisensi tidak valid atau sudah dinonaktifkan.'], 403);
}

if (!$row['hardware_id']) {
    json_response(['ok' => false, 'message' => 'Lisensi belum diaktifkan di komputer manapun.'], 403);
}

if ($row['hardware_id'] !== $hardwareId) {
    json_response(['ok' => false, 'message' => 'Lisensi sedang aktif di komputer lain.'], 409);
}

$stmt = $pdo->prepare('UPDATE activations SET last_check_at = NOW() WHERE license_id = ?');
$stmt->execute([$row['license_id']]);

json_response(['ok' => true, 'message' => 'Lisensi valid.']);
