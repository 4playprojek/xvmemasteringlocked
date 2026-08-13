<?php
// Admin FORCE release - tidak perlu hardware_id cocok, buat kasus
// komputer lama sudah tidak bisa diakses.
require_once __DIR__ . '/auth.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $licenseId = (int)($_POST['license_id'] ?? 0);
    if ($licenseId > 0) {
        $pdo = db();
        $stmt = $pdo->prepare('DELETE FROM activations WHERE license_id = ?');
        $stmt->execute([$licenseId]);
    }
}
header('Location: index.php');
exit;
