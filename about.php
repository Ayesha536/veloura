<?php
/**
 * VELOURA — About page.
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'About Us';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero" style="background-image:url('<?= url('assets/images/misc/about-2.jpg') ?>')">
  <div class="container">
    <p class="section-eyebrow light">Our Story</p>
    <h1 class="page-hero-title">Elegance is an <em>Attitude</em></h1>
  </div>
</section>

<section class="section-pad">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="about-img-wrap reveal">
          <img src="<?= url('assets/images/misc/about-1.jpg') ?>" alt="The Veloura atelier" class="about-img-main">
          <img src="<?= url('assets/images/misc/craft-1.jpg') ?>" alt="Craftsmanship" class="about-img-float">
          <div class="about-exp">
            <strong>10+</strong>
            <span>years of<br>craftsmanship</span>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <p class="section-eyebrow">Since 2016</p>
        <h2 class="section-title">The House of <em>Veloura</em></h2>
        <p class="about-text"><?= nl2br(e(get_setting('about_text', "Veloura was born from a simple belief: every woman deserves jewelry and accessories that feel like they were made just for her.\n\nWhat began as a small Paris atelier has grown into a boutique house of accessories, yet our promise is unchanged — pieces in soft lavender and polished silver, curated slowly, crafted carefully and delivered beautifully. From everyday pearls to evening clutches, every Veloura piece arrives in our signature lavender box, tied and ready to be treasured."))) ?></p>
        <div class="row g-3 mt-2">
          <div class="col-4"><div class="about-stat"><strong>28k+</strong><span>Happy clients</span></div></div>
          <div class="col-4"><div class="about-stat"><strong>450+</strong><span>Curated pieces</span></div></div>
          <div class="col-4"><div class="about-stat"><strong>32</strong><span>Countries served</span></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section-pad section-tint">
  <div class="container">
    <div class="section-head reveal">
      <p class="section-eyebrow">What Guides Us</p>
      <h2 class="section-title">Our <em>Values</em></h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="value-card reveal">
          <i class="bi bi-gem"></i>
          <h3>Curated Quality</h3>
          <p>Every stone, clasp and stitch is inspected by hand. We release few pieces, but each one is made to be worn for years, not seasons.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="value-card reveal" style="--d:1">
          <i class="bi bi-flower1"></i>
          <h3>Considered Design</h3>
          <p>Our palette of lavender and silver is intentional — calm, feminine and timeless. Design begins with how a piece makes you feel.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="value-card reveal" style="--d:2">
          <i class="bi bi-heart"></i>
          <h3>Graceful Service</h3>
          <p>From the first click to the unboxing, our team treats every order like a gift — because for someone, it always is.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section-pad">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6 order-lg-2">
        <div class="reveal">
          <img src="<?= url('assets/images/misc/craft-1.jpg') ?>" alt="Jewelry crafting" class="w-100 rounded-4 shadow-lg" style="object-fit:cover;max-height:460px">
        </div>
      </div>
      <div class="col-lg-6 order-lg-1">
        <p class="section-eyebrow">Behind the Scenes</p>
        <h2 class="section-title">Made in Our <em>Atelier</em></h2>
        <p class="about-text">Our studio pairs traditional techniques with modern precision. Silver is polished in three passes, pearls are hand-knotted, and every gift set is assembled at a single bench so it leaves our care as one perfect composition.</p>
        <ul class="care-list mt-3">
          <li>Hand-finished in small batches</li>
          <li>Responsibly sourced stones &amp; pearls</li>
          <li>Recyclable, plastic-free packaging</li>
        </ul>
        <a href="<?= url('shop.php') ?>" class="btn lux-btn mt-4">Explore the Collection <i class="bi bi-arrow-right ms-2"></i></a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
