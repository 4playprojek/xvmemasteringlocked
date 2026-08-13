<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../functions.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    if ($username !== '') {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO users (username) VALUES (?)');
            $stmt->execute([$username]);
            $userId = $pdo->lastInsertId();

            $licenseKey = generate_unique_license_key($pdo);
            $stmt = $pdo->prepare('INSERT INTO licenses (user_id, license_key, status) VALUES (?, ?, "active")');
            $stmt->execute([$userId, $licenseKey]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            header('Location: index.php?error=' . urlencode('Username sudah dipakai atau terjadi kesalahan.'));
            exit;
        }
    }
}
header('Location: index.php');
exit;
