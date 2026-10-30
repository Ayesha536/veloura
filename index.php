<?php
/**
 * VELOURA — Homepage
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Home';

// Active banners, ordered
$banners = db()->query('SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 4')->fetchAll();

// Categories for "shop by category"
$categories = get_categories();

// Featured products
$featured = db()->query(
    "SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 AND p.is_featured = 1
     ORDER BY p.id ASC LIMIT 8"
)->fetchAll();

// New arrivals
$newArrivals = db()->query(
    "SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1
     ORDER BY p.created_at DESC, p.id DESC LIMIT 4"
)->fetchAll();

// Wishlist ids for logged-in users
$inWishlistIds = [];
if (is_logged_in()) {
    $stmt = db()->prepare('SELECT product_id FROM wishlist WHERE user_id = ?');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $inWishlistIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

include __DIR__ . '/includes/header.php';
?>

<!-- ============ HERO CAROUSEL ============ -->
<section class="hero">
  <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="6000">
    <div class="carousel-inner">
      <?php if (!$banners): ?>
        <div class="carousel-item active hero-slide" style="background-image:url('<?= url('assets/images/banners/banner-1.jpg') ?>')">
          <div class="hero-overlay"></div>
          <div class="container hero-content">
            <p class="hero-eyebrow">— The New Season —</p>
            <h1 class="hero-title">Timeless Elegance,<br><em>Curated for You</em></h1>
            <p class="hero-sub">Discover jewelry, handbags and keepsakes in soft lavender &amp; silver.</p>
            <a href="<?= url('shop.php') ?>" class="btn lux-btn-gold">Shop the Collection <i class="bi bi-arrow-right ms-2"></i></a>
          </div>
        </div>
      <?php else: foreach ($banners as $i => $b): ?>
        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?> hero-slide" style="background-image:url('<?= url('assets/images/banners/' . $b['image']) ?>')">
          <div class="hero-overlay"></div>
          <div class="container hero-content">
            <?php if ($b['subtitle']): ?><p class="hero-eyebrow">— <?= e($b['subtitle']) ?> —</p><?php endif; ?>
            <h1 class="hero-title"><?= e($b['title']) ?></h1>
            <?php if ($b['button_text']): ?>
              <a href="<?= e($b['link'] ?: url('shop.php')) ?>" class="btn lux-btn-gold"><?= e($b['button_text']) ?> <i class="bi bi-arrow-right ms-2"></i></a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <?php if (count($banners) > 1): ?>
      <div class="carousel-indicators hero-indicators">
        <?php foreach ($banners as $i => $b): ?>
          <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $i ?>" <?= $i === 0 ? 'class="active" aria-current="true"' : '' ?> aria-label="Slide <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ============ USP STRIP ============ -->
<section class="usp-strip py-4">
  <div class="container">
    <div class="row g-3 text-center">
      <div class="col-6 col-lg-3">
        <div class="usp-item reveal"><i class="bi bi-truck"></i><div><h6>Free Shipping</h6><p>On orders over <?= money((float)get_setting('free_shipping_threshold', '150')) ?></p></div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="usp-item reveal" style="--d:1"><i class="bi bi-arrow-repeat"></i><div><h6>Easy Returns</h6><p>30-day graceful returns</p></div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="usp-item reveal" style="--d:2"><i class="bi bi-shield-check"></i><div><h6>Secure Payment</h6><p>Encrypted checkout</p></div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="usp-item reveal" style="--d:3"><i class="bi bi-gift"></i><div><h6>Signature Gift Box</h6><p>Every order, beautifully wrapped</p></div></div>
      </div>
    </div>
  </div>
</section>

<!-- ============ CATEGORIES ============ -->
<section class="section-pad">
  <div class="container">
    <div class="section-head reveal">
      <p class="section-eyebrow">Curated Selection</p>
      <h2 class="section-title">Shop by <em>Category</em></h2>
    </div>
    <div class="row g-4">
      <?php foreach ($categories as $i => $cat): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <a href="<?= url('shop.php?category=' . urlencode($cat['slug'])) ?>" class="category-card reveal" style="--d:<?= $i % 4 ?>">
            <div class="category-img">
              <img src="<?= url('assets/images/products/' . e($cat['image'])) ?>" alt="<?= e($cat['name']) ?>" loading="lazy">
            </div>
            <div class="category-info">
              <h3><?= e($cat['name']) ?></h3>
              <span><?= (int)$cat['product_count'] ?> pieces <i class="bi bi-arrow-right-short"></i></span>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FEATURED PRODUCTS ============ -->
<section class="section-pad section-tint">
  <div class="container">
    <div class="section-head d-flex flex-wrap align-items-end justify-content-between gap-3 reveal">
      <div>
        <p class="section-eyebrow">Signature Pieces</p>
        <h2 class="section-title mb-0">Our <em>Featured</em> Edit</h2>
      </div>
      <a href="<?= url('shop.php') ?>" class="lux-link">View all products <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-4">
      <?php foreach ($featured as $i => $product): include __DIR__ . '/includes/product_card.php'; endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ PROMO BAND ============ -->
<section class="promo-band">
  <div class="container">
    <div class="promo-inner reveal">
      <div class="promo-text">
        <p class="section-eyebrow light">Limited Time</p>
        <h2>The Lavender Nights Edit</h2>
        <p>Enjoy up to 25% off selected silver &amp; amethyst pieces this season.</p>
        <a href="<?= url('shop.php?sale=1') ?>" class="btn lux-btn-outline-light">Discover Offers</a>
      </div>
      <div class="promo-gem" aria-hidden="true"><i class="bi bi-gem"></i></div>
    </div>
  </div>
</section>

<!-- ============ NEW ARRIVALS ============ -->
<section class="section-pad">
  <div class="container">
    <div class="section-head d-flex flex-wrap align-items-end justify-content-between gap-3 reveal">
      <div>
        <p class="section-eyebrow">Fresh Arrivals</p>
        <h2 class="section-title mb-0">New <em>This Week</em></h2>
      </div>
      <a href="<?= url('shop.php?sort=newest') ?>" class="lux-link">See all new in <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-4">
      <?php foreach ($newArrivals as $i => $product): include __DIR__ . '/includes/product_card.php'; endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ TESTIMONIALS ============ -->
<section class="section-pad section-tint">
  <div class="container">
    <div class="section-head reveal">
      <p class="section-eyebrow">Words of Grace</p>
      <h2 class="section-title">Loved by <em>Our Clients</em></h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="testimonial-card reveal">
          <div class="quote-mark"><i class="bi bi-quote"></i></div>
          <?= stars_html(5) ?>
          <p>"The Coeur pendant exceeded every expectation — the packaging alone felt like a gift to myself. Veloura has become my little luxury ritual."</p>
          <div class="testimonial-author"><span class="avatar-circle">I</span><div><strong>Isabelle M.</strong><small>Paris, FR</small></div></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="testimonial-card reveal" style="--d:1">
          <div class="quote-mark"><i class="bi bi-quote"></i></div>
          <?= stars_html(5) ?>
          <p>"Ordered the Margaux tote as an anniversary gift — it arrived in the signature lavender box with a handwritten note. Simply flawless service."</p>
          <div class="testimonial-author"><span class="avatar-circle">S</span><div><strong>Sofia R.</strong><small>Milan, IT</small></div></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="testimonial-card reveal" style="--d:2">
          <div class="quote-mark"><i class="bi bi-quote"></i></div>
          <?= stars_html(4.5) ?>
          <p>"Elegant, understated pieces that go from boardroom to dinner. The silver watch is exquisite and shipping was remarkably fast."</p>
          <div class="testimonial-author"><span class="avatar-circle">A</span><div><strong>Amara K.</strong><small>London, UK</small></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
