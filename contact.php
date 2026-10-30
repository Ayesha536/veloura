<?php
/**
 * VELOURA — Contact page with working form (stores messages in DB).
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Contact';

$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old['name']    = trim((string)($_POST['name'] ?? ''));
    $old['email']   = trim((string)($_POST['email'] ?? ''));
    $old['subject'] = trim((string)($_POST['subject'] ?? ''));
    $old['message'] = trim((string)($_POST['message'] ?? ''));

    // ---- Server-side validation ----
    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100) {
        $errors['name'] = 'Please enter your name (2–100 characters).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (mb_strlen($old['subject']) < 3 || mb_strlen($old['subject']) > 150) {
        $errors['subject'] = 'Subject must be 3–150 characters.';
    }
    if (mb_strlen($old['message']) < 10 || mb_strlen($old['message']) > 3000) {
        $errors['message'] = 'Message must be 10–3000 characters.';
    }

    if (!$errors) {
        $stmt = db()->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
        $stmt->execute([$old['name'], $old['email'], $old['subject'], $old['message']]);
        set_flash('success', 'Thank you, ' . $old['name'] . '! Your message has been received — our concierge team replies within 24 hours.');
        redirect(url('contact.php'));
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-sm" style="background-image:url('<?= url('assets/images/banners/banner-2.jpg') ?>')">
  <div class="container">
    <p class="section-eyebrow light">We're Here for You</p>
    <h1 class="page-hero-title">Contact <em>Veloura</em></h1>
  </div>
</section>

<section class="section-pad">
  <div class="container">
    <div class="row g-4 mb-5">
      <div class="col-md-4">
        <div class="contact-card reveal">
          <i class="bi bi-geo-alt"></i>
          <h3>Visit Us</h3>
          <p><?= nl2br(e(get_setting('contact_address', '12 Rue de la Mode, 75008 Paris, France'))) ?></p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="contact-card reveal" style="--d:1">
          <i class="bi bi-chat-dots"></i>
          <h3>Reach Us</h3>
          <p><?= e(get_setting('contact_phone', '+33 1 45 22 08 90')) ?><br><?= e(get_setting('contact_email', 'care@veloura.com')) ?></p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="contact-card reveal" style="--d:2">
          <i class="bi bi-clock"></i>
          <h3>Concierge Hours</h3>
          <p><?= nl2br(e(get_setting('contact_hours', "Mon – Fri: 9:00 – 18:00\nSat: 10:00 – 16:00"))) ?></p>
        </div>
      </div>
    </div>

    <div class="row g-5 align-items-start">
      <div class="col-lg-5">
        <p class="section-eyebrow">Send a Message</p>
        <h2 class="section-title">How May We <em>Help?</em></h2>
        <p class="about-text">Whether it's a question about an order, a gift recommendation or a special request, our concierge team is delighted to assist. We reply to every message personally, usually within one business day.</p>
        <div class="detail-trust mt-4">
          <div class="trust-item"><i class="bi bi-shield-check"></i> Your details stay private</div>
          <div class="trust-item"><i class="bi bi-lightning"></i> 24-hour response promise</div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="lux-card p-4 p-md-5">
          <?php if ($errors): ?>
            <div class="alert alert-danger">Please fix the highlighted fields below.</div>
          <?php endif; ?>
          <form method="post" action="<?= url('contact.php') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label lux-label" for="cName">Your Name *</label>
                <input type="text" class="form-control lux-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="cName" name="name" value="<?= e($old['name']) ?>" required>
                <div class="invalid-feedback"><?= e($errors['name'] ?? '') ?></div>
              </div>
              <div class="col-md-6">
                <label class="form-label lux-label" for="cEmail">Email Address *</label>
                <input type="email" class="form-control lux-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="cEmail" name="email" value="<?= e($old['email']) ?>" required>
                <div class="invalid-feedback"><?= e($errors['email'] ?? '') ?></div>
              </div>
              <div class="col-12">
                <label class="form-label lux-label" for="cSubject">Subject *</label>
                <input type="text" class="form-control lux-input <?= isset($errors['subject']) ? 'is-invalid' : '' ?>" id="cSubject" name="subject" value="<?= e($old['subject']) ?>" required>
                <div class="invalid-feedback"><?= e($errors['subject'] ?? '') ?></div>
              </div>
              <div class="col-12">
                <label class="form-label lux-label" for="cMessage">Message *</label>
                <textarea class="form-control lux-input <?= isset($errors['message']) ? 'is-invalid' : '' ?>" id="cMessage" name="message" rows="6" required><?= e($old['message']) ?></textarea>
                <div class="invalid-feedback"><?= e($errors['message'] ?? '') ?></div>
              </div>
              <div class="col-12">
                <button type="submit" class="btn lux-btn-gold w-100 py-3">Send Message <i class="bi bi-send ms-2"></i></button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
