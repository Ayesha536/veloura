<?php
/**
 * VELOURA — Customer signup (role is ALWAYS 'customer' — no public admin registration).
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Create Account';

if (is_logged_in()) {
    redirect(url('index.php'));
}

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old['name']     = trim((string)($_POST['name'] ?? ''));
    $old['email']    = strtolower(trim((string)($_POST['email'] ?? '')));
    $old['phone']    = trim((string)($_POST['phone'] ?? ''));
    $password        = (string)($_POST['password'] ?? '');
    $confirm         = (string)($_POST['password_confirm'] ?? '');
    $agree           = isset($_POST['agree']);

    // ---- Server-side validation ----
    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100) {
        $errors['name'] = 'Please enter your full name (2–100 characters).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($old['phone'] !== '' && !preg_match('/^[0-9 +\-().]{6,20}$/', $old['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number or leave it empty.';
    }
    if (!valid_password($password)) {
        $errors['password'] = 'Password must be at least 8 characters and include letters and numbers.';
    }
    if ($password !== $confirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }
    if (!$agree) {
        $errors['agree'] = 'Please agree to the Terms & Privacy Policy.';
    }

    // Unique email
    if (!isset($errors['email'])) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email already exists. Try signing in.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare(
            "INSERT INTO users (name, email, password, phone, role, status, created_at)
             VALUES (?, ?, ?, ?, 'customer', 'active', NOW())"
        );
        $stmt->execute([$old['name'], $old['email'], hash_password($password), $old['phone']]);

        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$old['email']]);
        $user = $stmt->fetch();

        login_user($user);
        set_flash('success', 'Welcome to Veloura, ' . $user['name'] . '! Your account is ready.');
        redirect(url('index.php'));
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="auth-card reveal">
          <div class="auth-head">
            <span class="brand-mark lg">V</span>
            <h1>Create Account</h1>
            <p>Join the world of Veloura — wishlists, order tracking &amp; members-only offers</p>
          </div>

          <form method="post" action="<?= url('signup.php') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label lux-label" for="regName">Full Name *</label>
              <input type="text" class="form-control lux-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="regName" name="name" value="<?= e($old['name']) ?>" required autocomplete="name">
              <div class="invalid-feedback"><?= e($errors['name'] ?? '') ?></div>
            </div>
            <div class="row">
              <div class="col-md-7 mb-3">
                <label class="form-label lux-label" for="regEmail">Email Address *</label>
                <input type="email" class="form-control lux-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="regEmail" name="email" value="<?= e($old['email']) ?>" required autocomplete="email">
                <div class="invalid-feedback"><?= e($errors['email'] ?? '') ?></div>
              </div>
              <div class="col-md-5 mb-3">
                <label class="form-label lux-label" for="regPhone">Phone <span class="text-muted">(optional)</span></label>
                <input type="tel" class="form-control lux-input <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="regPhone" name="phone" value="<?= e($old['phone']) ?>" autocomplete="tel">
                <div class="invalid-feedback"><?= e($errors['phone'] ?? '') ?></div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label lux-label" for="regPassword">Password *</label>
                <div class="position-relative">
                  <input type="password" class="form-control lux-input pe-5 <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="regPassword" name="password" required autocomplete="new-password">
                  <button type="button" class="toggle-pw" data-target="regPassword" aria-label="Show password"><i class="bi bi-eye"></i></button>
                  <div class="invalid-feedback"><?= e($errors['password'] ?? '') ?></div>
                </div>
                <div class="form-text">Min 8 characters with letters &amp; numbers.</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label lux-label" for="regConfirm">Confirm Password *</label>
                <input type="password" class="form-control lux-input <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" id="regConfirm" name="password_confirm" required autocomplete="new-password">
                <div class="invalid-feedback"><?= e($errors['password_confirm'] ?? '') ?></div>
              </div>
            </div>
            <div class="mb-4">
              <div class="form-check lux-check">
                <input class="form-check-input <?= isset($errors['agree']) ? 'is-invalid' : '' ?>" type="checkbox" id="regAgree" name="agree" value="1">
                <label class="form-check-label" for="regAgree">I agree to the Terms of Service &amp; Privacy Policy</label>
              </div>
              <?php if (isset($errors['agree'])): ?><div class="text-danger small mt-1"><?= e($errors['agree']) ?></div><?php endif; ?>
            </div>
            <button type="submit" class="btn lux-btn-gold w-100 py-3">Create My Account</button>
          </form>

          <p class="auth-alt">Already a member? <a href="<?= url('login.php') ?>">Sign in</a></p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
