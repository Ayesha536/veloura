<?php
/**
 * VELOURA — FAQ page (content managed from admin).
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'FAQ';
$faqs = db()->query('SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-sm" style="background-image:url('<?= url('assets/images/banners/banner-3.jpg') ?>')">
  <div class="container">
    <p class="section-eyebrow light">Good to Know</p>
    <h1 class="page-hero-title">Frequently Asked <em>Questions</em></h1>
  </div>
</section>

<section class="section-pad">
  <div class="container" style="max-width:860px">
    <?php if (!$faqs): ?>
      <div class="empty-state text-center py-5">
        <i class="bi bi-patch-question"></i>
        <h3>No FAQs published yet</h3>
        <p class="text-muted">The team is preparing our help content — check back soon.</p>
      </div>
    <?php else: ?>
      <div class="accordion lux-accordion" id="faqAccordion">
        <?php foreach ($faqs as $i => $faq): ?>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= (int)$faq['id'] ?>" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>">
                <?= e($faq['question']) ?>
              </button>
            </h2>
            <div id="faq<?= (int)$faq['id'] ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
              <div class="accordion-body"><?= nl2br(e($faq['answer'])) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="text-center mt-5">
        <p class="text-muted">Can't find your answer?</p>
        <a href="<?= url('contact.php') ?>" class="btn lux-btn">Ask Our Concierge <i class="bi bi-arrow-right ms-2"></i></a>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
