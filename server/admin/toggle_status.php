<?php
// Admin: aktifkan / nonaktifkan (revoke) sebuah lisensi.
require_once __DIR__ . '/auth.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $licenseId = (int)($_POST['license_id'] ?? 0);
    $newStatus = ($_POST['new_status'] ?? '') === 'active' ? 'active' : 'revoked';
    if ($licenseId > 0) {
        $pdo = db();
        $stmt = $pdo->prepare('UPDATE licenses SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $licenseId]);
    }
}
header('Location: index.php');
exit;
