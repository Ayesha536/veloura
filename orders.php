<?php
/**
 * VELOURA — Customer order history.
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'My Orders';

require_login();

$stmt = db()->prepare(
    "SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
     FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC"
);
$stmt->execute([(int)$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb" class="lux-breadcrumb">
  <div class="container">
    <h1 class="page-title">My Orders</h1>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Order History</li>
    </ol>
  </div>
</nav>

<section class="section-pad pt-4">
  <div class="container">
    <?php if (!$orders): ?>
      <div class="empty-state text-center py-5">
        <i class="bi bi-receipt"></i>
        <h3>No orders yet</h3>
        <p class="text-muted">When you place an order, it will appear here with live status tracking.</p>
        <a href="<?= url('shop.php') ?>" class="btn lux-btn mt-2">Discover Pieces <i class="bi bi-arrow-right ms-2"></i></a>
      </div>
    <?php else: ?>
      <div class="orders-list">
        <?php foreach ($orders as $o): ?>
          <div class="lux-card order-row">
            <div class="order-col order-id">
              <span class="order-label">Order</span>
              <strong><?= e($o['order_number']) ?></strong>
              <span class="order-date"><?= date('M j, Y', strtotime($o['created_at'])) ?></span>
            </div>
            <div class="order-col">
              <span class="order-label">Items</span>
              <strong><?= (int)$o['item_count'] ?> piece<?= (int)$o['item_count'] > 1 ? 's' : '' ?></strong>
            </div>
            <div class="order-col">
              <span class="order-label">Total</span>
              <strong><?= money((float)$o['total']) ?></strong>
              <span class="order-date"><?= e(strtoupper($o['payment_method'])) ?> · <?= e($o['payment_status']) ?></span>
            </div>
            <div class="order-col">
              <span class="order-label">Status</span>
              <?= order_status_badge($o['status']) ?>
            </div>
            <div class="order-col order-action">
              <a href="<?= url('order_details.php?id=' . (int)$o['id']) ?>" class="btn lux-btn btn-sm">View Details</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
