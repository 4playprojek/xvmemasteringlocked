<?php
require_once __DIR__ . '/config.php';

/**
 * Generate license key format: XXXX-XXXX-XXXX-XXXX
 * Karakter ambigu (0/O, 1/I/L) sengaja dihilangkan biar gampang dibaca/diketik.
 */
function generate_license_key(): string {
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $segments = [];
    for ($s = 0; $s < 4; $s++) {
        $seg = '';
        for ($i = 0; $i < 4; $i++) {
            $seg .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $segments[] = $seg;
    }
    return implode('-', $segments);
}

/** Pastikan key yang digenerate belum pernah dipakai */
function generate_unique_license_key(PDO $pdo): string {
    do {
        $key = generate_license_key();
        $stmt = $pdo->prepare('SELECT id FROM licenses WHERE license_key = ?');
        $stmt->execute([$key]);
    } while ($stmt->fetch());
    return $key;
}

function json_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
