<?php
/**
 * VELOURA — Customer login.
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Sign In';

// Already signed in? Leave.
if (is_logged_in()) {
    redirect(url('index.php'));
}

$errors = [];
$old   = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $throttleKey = 'login_web';
    $old['email'] = strtolower(trim((string)($_POST['email'] ?? '')));
    $password     = (string)($_POST['password'] ?? '');

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors['password'] = 'Please enter your password.';
    }
    if (!throttle_check($throttleKey)) {
        $errors['general'] = 'Too many failed attempts. Please wait 5 minutes and try again.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$old['email']]);
        $user = $stmt->fetch();

        if ($user && verify_password($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                $errors['general'] = 'This account has been suspended. Please contact support.';
            } else {
                throttle_clear($throttleKey);
                login_user($user);
                set_flash('success', 'Welcome back, ' . $user['name'] . '! You are now signed in.');
                $target = $_SESSION['redirect_after_login'] ?? url('index.php');
                unset($_SESSION['redirect_after_login']);
                // Only allow internal redirects
                if (!is_string($target) || strpos($target, '/') !== 0 || strpos($target, '//') === 0) {
                    $target = url('index.php');
                }
                redirect($target);
            }
        } else {
            throttle_fail($throttleKey);
            $errors['general'] = 'Incorrect email or password. Please try again.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-7 col-lg-5">
        <div class="auth-card reveal">
          <div class="auth-head">
            <span class="brand-mark lg">V</span>
            <h1>Welcome Back</h1>
            <p>Sign in to your Veloura account</p>
          </div>

          <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger py-2"><?= e($errors['general']) ?></div>
          <?php endif; ?>

          <form method="post" action="<?= url('login.php') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label lux-label" for="loginEmail">Email Address</label>
              <input type="email" class="form-control lux-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="loginEmail" name="email" value="<?= e($old['email']) ?>" required autofocus autocomplete="email">
              <div class="invalid-feedback"><?= e($errors['email'] ?? '') ?></div>
            </div>
            <div class="mb-4">
              <label class="form-label lux-label" for="loginPassword">Password</label>
              <div class="position-relative">
                <input type="password" class="form-control lux-input pe-5 <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="loginPassword" name="password" required autocomplete="current-password">
                <button type="button" class="toggle-pw" data-target="loginPassword" aria-label="Show password"><i class="bi bi-eye"></i></button>
                <div class="invalid-feedback"><?= e($errors['password'] ?? '') ?></div>
              </div>
            </div>
            <button type="submit" class="btn lux-btn-gold w-100 py-3">Sign In</button>
          </form>

          <p class="auth-alt">New to Veloura? <a href="<?= url('signup.php') ?>">Create an account</a></p>

          <div class="demo-creds">
            <p class="mb-1"><strong>Demo customer</strong> — emma@example.com / User@123</p>
            <p class="mb-0"><strong>Admin panel</strong> — <a href="<?= url('admin/login.php') ?>">admin login</a> · admin@veloura.com / Admin@123</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
