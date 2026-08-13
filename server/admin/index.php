<?php
require_once __DIR__ . '/auth.php';
require_admin_login();

$pdo = db();
$rows = $pdo->query(
    'SELECT u.id AS user_id, u.username, u.created_at AS user_created_at,
            l.id AS license_id, l.license_key, l.status,
            a.hardware_id, a.computer_name, a.activated_at, a.last_check_at
     FROM users u
     JOIN licenses l ON l.user_id = u.id
     LEFT JOIN activations a ON a.license_id = l.id
     ORDER BY u.created_at DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>XVME License Admin</title>
<style>
  body { font-family: system-ui, sans-serif; background:#0d0c14; color:#eee; margin:0; padding:32px; }
  h1 { color:#8b7bff; margin-top:0; }
  .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
  a.logout { color:#ff6b6b; text-decoration:none; font-size:14px; }
  .card { background:#17151f; border-radius:12px; padding:20px; margin-bottom:24px; box-shadow:0 8px 30px rgba(0,0,0,.3); }
  form.inline { display:flex; gap:10px; }
  input[type=text] { flex:1; padding:10px; border-radius:6px; border:1px solid #333; background:#0d0c14; color:#eee; }
  button { padding:10px 18px; border:none; border-radius:6px; background:#8b7bff; color:#fff; font-weight:600; cursor:pointer; }
  button.danger { background:#ff6b6b; }
  button.small { padding:6px 10px; font-size:12px; }
  table { width:100%; border-collapse:collapse; margin-top:8px; }
  th, td { text-align:left; padding:10px 8px; border-bottom:1px solid #2a2836; font-size:14px; }
  th { color:#9a95b3; font-weight:600; }
  .key { font-family: monospace; letter-spacing:1px; color:#2dd4bf; }
  .badge { padding:3px 8px; border-radius:20px; font-size:12px; font-weight:600; }
  .badge.active { background:#16352c; color:#2dd4bf; }
  .badge.revoked { background:#3a1e1e; color:#ff6b6b; }
  .badge.used { background:#2a2440; color:#8b7bff; }
  .badge.free { background:#232130; color:#9a95b3; }
  .error { color:#ff6b6b; margin-bottom:12px; }
</style>
</head>
<body>
<div class="topbar">
  <h1>XVME License Admin</h1>
  <a class="logout" href="logout.php">Keluar</a>
</div>

<?php if (!empty($_GET['error'])): ?>
  <div class="error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="card">
  <h3>Daftarkan user baru (license key digenerate otomatis)</h3>
  <form class="inline" method="post" action="add_user.php">
    <input type="text" name="username" placeholder="Username baru" required>
    <button type="submit">Buat & Generate License</button>
  </form>
</div>

<div class="card">
  <h3>Daftar User & Lisensi</h3>
  <table>
    <tr>
      <th>Username</th>
      <th>License Key</th>
      <th>Status</th>
      <th>Dipakai di</th>
      <th>Terakhir cek</th>
      <th>Aksi</th>
    </tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['username']) ?></td>
      <td class="key"><?= htmlspecialchars($r['license_key']) ?></td>
      <td><span class="badge <?= $r['status'] ?>"><?= $r['status'] === 'active' ? 'Aktif' : 'Revoked' ?></span></td>
      <td>
        <?php if ($r['hardware_id']): ?>
          <span class="badge used">Terkunci</span>
          <?= htmlspecialchars($r['computer_name'] ?: $r['hardware_id']) ?>
        <?php else: ?>
          <span class="badge free">Belum dipakai</span>
        <?php endif; ?>
      </td>
      <td><?= $r['last_check_at'] ? htmlspecialchars($r['last_check_at']) : '-' ?></td>
      <td style="display:flex; gap:6px;">
        <?php if ($r['hardware_id']): ?>
          <form method="post" action="release.php" onsubmit="return confirm('Paksa lepas lisensi ini dari komputer yang menguncinya?');">
            <input type="hidden" name="license_id" value="<?= $r['license_id'] ?>">
            <button class="small" type="submit">Lepas Paksa</button>
          </form>
        <?php endif; ?>
        <form method="post" action="toggle_status.php">
          <input type="hidden" name="license_id" value="<?= $r['license_id'] ?>">
          <input type="hidden" name="new_status" value="<?= $r['status'] === 'active' ? 'revoked' : 'active' ?>">
          <button class="small danger" type="submit"><?= $r['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
      <tr><td colspan="6" style="text-align:center; color:#666;">Belum ada user.</td></tr>
    <?php endif; ?>
  </table>
</div>

</body>
</html>
