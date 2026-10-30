<?php
/**
 * VELOURA — Admin login (separate secure entry; no public registration for admins).
 */
require_once __DIR__ . '/../config/config.php';

if (current_admin() !== null) {
    redirect(url('admin/index.php'));
}

$errors = [];
$old = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $throttleKey = 'login_admin';
    $old['email'] = strtolower(trim((string)($_POST['email'] ?? '')));
    $password     = (string)($_POST['password'] ?? '');

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email.';
    }
    if ($password === '') {
        $errors['password'] = 'Enter your password.';
    }
    if (!throttle_check($throttleKey)) {
        $errors['general'] = 'Too many failed attempts. Locked for 5 minutes.';
    }

    if (!$errors) {
        $stmt = db()->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $stmt->execute([$old['email']]);
        $admin = $stmt->fetch();

        if ($admin && verify_password($password, $admin['password']) && $admin['status'] === 'active') {
            throttle_clear($throttleKey);
            login_admin($admin);
            redirect(url('admin/index.php'));
        } else {
            throttle_fail($throttleKey);
            $errors['general'] = 'Invalid administrator credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Sign In — Veloura</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%23372a45'/%3E%3Ctext x='16' y='22' font-family='Georgia' font-size='17' fill='%23e9d9f7' text-anchor='middle'%3EV%3C/text%3E%3C/svg%3E">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>?v=1.0">
</head>
<body class="admin-login-body">
<div class="admin-login-wrap">
  <div class="admin-login-card">
    <div class="text-center mb-4">
      <span class="brand-mark lg">V</span>
      <h1 class="admin-login-title">Veloura Admin</h1>
      <p class="text-muted small mb-0">Authorized personnel only</p>
    </div>

    <?= render_flashes() ?>

    <?php if (isset($errors['general'])): ?>
      <div class="alert alert-danger py-2"><i class="bi bi-shield-exclamation me-2"></i><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= url('admin/login.php') ?>" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label lux-label" for="admEmail">Admin Email</label>
        <input type="email" id="admEmail" name="email" class="form-control lux-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= e($old['email']) ?>" required autofocus autocomplete="username">
        <div class="invalid-feedback"><?= e($errors['email'] ?? '') ?></div>
      </div>
      <div class="mb-4">
        <label class="form-label lux-label" for="admPassword">Password</label>
        <div class="position-relative">
          <input type="password" id="admPassword" name="password" class="form-control lux-input pe-5" required autocomplete="current-password">
          <button type="button" class="toggle-pw" data-target="admPassword" aria-label="Show password"><i class="bi bi-eye"></i></button>
          <div class="invalid-feedback"><?= e($errors['password'] ?? '') ?></div>
        </div>
      </div>
      <button type="submit" class="btn lux-btn-gold w-100 py-3"><i class="bi bi-shield-lock me-2"></i>Sign In to Dashboard</button>
    </form>

    <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
    <div class="demo-creds mt-4">
      <p class="mb-1"><strong>Default admin</strong> — admin@veloura.com / Admin@123</p>
      <p class="mb-0">Set <code>APP_DEBUG</code> to <code>false</code> to hide this hint.</p>
    </div>
    <?php endif; ?>

    <a href="<?= url('index.php') ?>" class="d-block text-center mt-4 small text-muted"><i class="bi bi-arrow-left me-1"></i>Back to storefront</a>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/admin.js') ?>?v=1.0"></script>
</body>
</html>
