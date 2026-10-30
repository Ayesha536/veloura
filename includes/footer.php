<?php /* VELOURA — Global footer (storefront) */ ?>
</main>

<footer class="lux-footer mt-5">
  <div class="container">
    <div class="row gy-4 pb-4">
      <div class="col-lg-4 col-md-6">
        <a class="footer-brand mb-3 d-inline-block" href="<?= url('index.php') ?>">
          <span class="brand-mark">V</span><span class="brand-text">VELOURA</span>
        </a>
        <p class="footer-desc"><?= e(get_setting('footer_about', "Veloura curates premium women's accessories in lavender and silver — jewelry, handbags and keepsakes crafted to be treasured.")) ?></p>
        <div class="footer-social">
          <a href="<?= e(get_setting('social_instagram', '#')) ?>" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="<?= e(get_setting('social_facebook', '#')) ?>" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="<?= e(get_setting('social_pinterest', '#')) ?>" aria-label="Pinterest"><i class="bi bi-pinterest"></i></a>
          <a href="<?= e(get_setting('social_twitter', '#')) ?>" aria-label="X / Twitter"><i class="bi bi-twitter-x"></i></a>
        </div>
      </div>

      <div class="col-lg-2 col-md-6 col-6">
        <h6 class="footer-title">Shop</h6>
        <ul class="footer-links">
          <?php foreach (array_slice(get_categories(), 0, 6) as $cat): ?>
            <li><a href="<?= url('shop.php?category=' . urlencode($cat['slug'])) ?>"><?= e($cat['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="col-lg-2 col-md-6 col-6">
        <h6 class="footer-title">Company</h6>
        <ul class="footer-links">
          <li><a href="<?= url('about.php') ?>">Our Story</a></li>
          <li><a href="<?= url('faq.php') ?>">FAQ</a></li>
          <li><a href="<?= url('contact.php') ?>">Contact Us</a></li>
          <li><a href="<?= url('shop.php') ?>">New Arrivals</a></li>
          <li><a href="<?= url('orders.php') ?>">Track Order</a></li>
        </ul>
      </div>

      <div class="col-lg-4 col-md-6">
        <h6 class="footer-title">Stay in Touch</h6>
        <p class="footer-desc">Join the Veloura list for private previews, new pieces and members-only offers.</p>
        <form class="newsletter-form" id="newsletterForm">
          <div class="input-group">
            <input type="email" class="form-control" id="newsletterEmail" placeholder="Your email address" required>
            <button class="btn" type="submit" id="newsletterBtn">Subscribe</button>
          </div>
          <div class="form-text" id="newsletterMsg"></div>
        </form>
        <div class="footer-contact mt-3">
          <p class="mb-1"><i class="bi bi-geo-alt me-2"></i><?= e(get_setting('contact_address', '12 Rue de la Mode, Paris')) ?></p>
          <p class="mb-1"><i class="bi bi-envelope me-2"></i><?= e(get_setting('contact_email', 'care@veloura.com')) ?></p>
          <p class="mb-0"><i class="bi bi-telephone me-2"></i><?= e(get_setting('contact_phone', '+33 1 45 22 08 90')) ?></p>
        </div>
      </div>
    </div>

    <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center py-3">
      <span>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</span>
      <div class="pay-badges mt-2 mt-md-0">
        <span class="pay-badge"><i class="bi bi-credit-card"></i> Visa</span>
        <span class="pay-badge"><i class="bi bi-credit-card-2-front"></i> Mastercard</span>
        <span class="pay-badge"><i class="bi bi-paypal"></i> PayPal</span>
        <span class="pay-badge"><i class="bi bi-cash-coin"></i> COD</span>
      </div>
    </div>
  </div>
</footer>

<button type="button" class="back-to-top" id="backToTop" aria-label="Back to top"><i class="bi bi-arrow-up"></i></button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/main.js') ?>?v=1.0"></script>
</body>
</html>
