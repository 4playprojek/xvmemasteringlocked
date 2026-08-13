<?php
require_once __DIR__ . '/../config.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if ($u === ADMIN_USERNAME && password_verify($p, ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    }
    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Login Admin - XVME License</title>
<style>
  body { font-family: system-ui, sans-serif; background:#0d0c14; color:#eee; display:flex; align-items:center; justify-content:center; height:100vh; margin:0; }
  form { background:#17151f; padding:32px; border-radius:12px; width:320px; box-shadow:0 8px 30px rgba(0,0,0,.4); }
  h2 { margin-top:0; color:#8b7bff; }
  input { width:100%; padding:10px; margin:8px 0 16px; border-radius:6px; border:1px solid #333; background:#0d0c14; color:#eee; box-sizing:border-box; }
  button { width:100%; padding:10px; border:none; border-radius:6px; background:#8b7bff; color:#fff; font-weight:600; cursor:pointer; }
  .err { color:#ff6b6b; margin-bottom:12px; }
</style>
</head>
<body>
<form method="post">
  <h2>XVME License Admin</h2>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <label>Username</label>
  <input type="text" name="username" required autofocus>
  <label>Password</label>
  <input type="password" name="password" required>
  <button type="submit">Masuk</button>
</form>
</body>
</html>
