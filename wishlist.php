<?php
/**
 * VELOURA — Wishlist page (database-backed, requires login).
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'My Wishlist';

require_login();

$stmt = db()->prepare(
    "SELECT p.*, c.name AS category_name FROM wishlist w
     JOIN products p ON p.id = w.product_id
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE w.user_id = ? AND p.is_active = 1
     ORDER BY w.created_at DESC"
);
$stmt->execute([(int)$_SESSION['user_id']]);
$products = $stmt->fetchAll();
$inWishlistIds = array_map(fn($r) => (int)$r['id'], $products);

include __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb" class="lux-breadcrumb">
  <div class="container">
    <h1 class="page-title">My Wishlist</h1>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Wishlist</li>
    </ol>
  </div>
</nav>

<section class="section-pad pt-4">
  <div class="container">
    <?php if (!$products): ?>
      <div class="empty-state text-center py-5">
        <i class="bi bi-heart"></i>
        <h3>Your wishlist is empty</h3>
        <p class="text-muted">Tap the heart on any piece to save it here for later.</p>
        <a href="<?= url('shop.php') ?>" class="btn lux-btn mt-2">Explore the Boutique <i class="bi bi-arrow-right ms-2"></i></a>
      </div>
    <?php else: ?>
      <p class="text-muted mb-4"><?= count($products) ?> saved piece<?= count($products) > 1 ? 's' : '' ?> — tap the heart again to remove.</p>
      <div class="row g-4">
        <?php foreach ($products as $i => $product): include __DIR__ . '/includes/product_card.php'; endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
