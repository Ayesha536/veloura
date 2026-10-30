<?php
/**
 * VELOURA — Order details (customer can only view their own orders).
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Order Details';

require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    "SELECT o.*, u.name AS user_name FROM orders o JOIN users u ON u.id = o.user_id
     WHERE o.id = ? AND o.user_id = ? LIMIT 1"
);
$stmt->execute([$id, (int)$_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect(url('orders.php'));
}

$itemsStmt = db()->prepare(
    "SELECT oi.*, p.slug AS product_slug, p.image AS product_image
     FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?"
);
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

$statusSteps = ['Pending', 'Processing', 'Shipped', 'Delivered'];
$currentStep = array_search($order['status'], $statusSteps, true);
if ($order['status'] === 'Cancelled') $currentStep = false;

include __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb" class="lux-breadcrumb">
  <div class="container">
    <h1 class="page-title">Order <?= e($order['order_number']) ?></h1>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
      <li class="breadcrumb-item"><a href="<?= url('orders.php') ?>">My Orders</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= e($order['order_number']) ?></li>
    </ol>
  </div>
</nav>

<section class="section-pad pt-4">
  <div class="container">
    <?php if ($order['status'] === 'Cancelled'): ?>
      <div class="alert alert-warning"><i class="bi bi-x-circle me-2"></i>This order was cancelled. If this wasn't you, please contact our concierge.</div>
    <?php endif; ?>

    <!-- Status timeline -->
    <?php if ($currentStep !== false): ?>
      <div class="lux-card p-4 p-md-5 mb-4">
        <div class="order-timeline">
          <?php foreach ($statusSteps as $i => $step): ?>
            <div class="timeline-step <?= $i <= $currentStep ? 'done' : '' ?>">
              <span class="timeline-dot"><i class="bi bi-<?= $i <= $currentStep ? 'check' : 'circle' ?>"></i></span>
              <span class="timeline-label"><?= $step ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="timeline-line"><div class="timeline-progress" style="width: <?= $currentStep / (count($statusSteps) - 1) * 100 ?>%"></div></div>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="lux-card p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="summary-title mb-0">Items in This Order</h3>
            <?= order_status_badge($order['status']) ?>
          </div>
          <?php foreach ($items as $it): ?>
            <div class="mini-item">
              <?php if ($it['product_image']): ?>
                <img src="<?= url('assets/images/products/' . e($it['product_image'])) ?>" alt="<?= e($it['product_name']) ?>">
              <?php else: ?>
                <span class="mini-item-placeholder"><i class="bi bi-gem"></i></span>
              <?php endif; ?>
              <div class="flex-grow-1">
                <?php if ($it['product_slug']): ?>
                  <a href="<?= url('product.php?slug=' . urlencode($it['product_slug'])) ?>" class="fw-medium"><?= e($it['product_name']) ?></a>
                <?php else: ?>
                  <span class="fw-medium"><?= e($it['product_name']) ?></span>
                <?php endif; ?>
                <br><small class="text-muted"><?= (int)$it['quantity'] ?> × <?= money((float)$it['unit_price']) ?></small>
              </div>
              <strong><?= money((float)$it['unit_price'] * (int)$it['quantity']) ?></strong>
            </div>
          <?php endforeach; ?>
          <div class="summary-divider"></div>
          <div class="summary-row"><span>Subtotal</span><strong><?= money((float)$order['subtotal']) ?></strong></div>
          <div class="summary-row"><span>Shipping</span><strong><?= (float)$order['shipping_fee'] > 0 ? money((float)$order['shipping_fee']) : 'Free' ?></strong></div>
          <div class="summary-row total"><span>Total</span><strong><?= money((float)$order['total']) ?></strong></div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="lux-card p-4 mb-4">
          <h3 class="summary-title">Delivery Address</h3>
          <address class="order-address">
            <strong><?= e($order['shipping_name']) ?></strong><br>
            <?= e($order['shipping_address']) ?><br>
            <?= e($order['shipping_city']) ?><?= $order['shipping_state'] ? ', ' . e($order['shipping_state']) : '' ?> <?= e($order['shipping_zip']) ?><br>
            <?= e($order['shipping_country']) ?><br>
            <span class="text-muted"><?= e($order['shipping_phone']) ?> · <?= e($order['shipping_email']) ?></span>
          </address>
        </div>
        <div class="lux-card p-4">
          <h3 class="summary-title">Payment &amp; Notes</h3>
          <div class="summary-row"><span>Method</span><strong><?= e(ucfirst($order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Card')) ?></strong></div>
          <div class="summary-row"><span>Payment</span><strong><?= e($order['payment_status']) ?></strong></div>
          <div class="summary-row"><span>Placed</span><strong><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></strong></div>
          <?php if ($order['notes']): ?>
            <div class="summary-divider"></div>
            <p class="mb-0 small"><strong>Notes:</strong> <?= nl2br(e($order['notes'])) ?></p>
          <?php endif; ?>
        </div>
        <a href="<?= url('shop.php') ?>" class="btn lux-btn-ghost w-100 mt-4">Continue Shopping</a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
