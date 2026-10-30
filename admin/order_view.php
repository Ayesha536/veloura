<?php
/**
 * VELOURA — Admin: view a single order & update its status / payment status.
 */
require_once __DIR__ . '/../config/config.php';
require_admin();
$activeNav = 'order_view';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    "SELECT o.*, u.name AS customer_name, u.email AS user_email, u.phone AS user_phone
     FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ? LIMIT 1"
);
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect(url('admin/orders.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_status') {
        $newStatus = (string)($_POST['status'] ?? '');
        $newPayment = (string)($_POST['payment_status'] ?? '');
        if (in_array($newStatus, ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'], true)
            && in_array($newPayment, ['Unpaid', 'Paid', 'Refunded'], true)) {
            db()->prepare('UPDATE orders SET status = ?, payment_status = ? WHERE id = ?')
                ->execute([$newStatus, $newPayment, $id]);
            set_flash('success', 'Order ' . $order['order_number'] . ' updated: status ' . $newStatus . ', payment ' . $newPayment . '.');
        } else {
            set_flash('error', 'Invalid status values.');
        }
        redirect(url('admin/order_view.php?id=' . $id));
    }
}

$itemsStmt = db()->prepare(
    "SELECT oi.*, p.slug AS product_slug, p.image AS product_image
     FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?"
);
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

$pageTitle = 'Order ' . $order['order_number'];
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="panel-card">
      <div class="panel-head d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2><i class="bi bi-receipt me-2"></i>Order <?= e($order['order_number']) ?></h2>
        <?= order_status_badge($order['status']) ?>
      </div>
      <div class="panel-body">
        <p class="text-muted small mb-3">Placed <?= date('M j, Y — g:i A', strtotime($order['created_at'])) ?> · Payment via <?= e(ucfirst($order['payment_method'])) ?></p>

        <?php foreach ($items as $it): ?>
          <div class="mini-item">
            <?php if ($it['product_image']): ?>
              <img src="<?= url('assets/images/products/' . e($it['product_image'])) ?>" alt="">
            <?php else: ?>
              <span class="mini-item-placeholder"><i class="bi bi-gem"></i></span>
            <?php endif; ?>
            <div class="flex-grow-1">
              <?php if ($it['product_slug']): ?>
                <a href="<?= url('product.php?slug=' . urlencode($it['product_slug'])) ?>" target="_blank" class="fw-medium"><?= e($it['product_name']) ?></a>
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

        <?php if ($order['notes']): ?>
          <div class="summary-divider"></div>
          <p class="mb-0 small"><strong>Customer notes:</strong> <?= nl2br(e($order['notes'])) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="panel-card mb-4">
      <div class="panel-head"><h2><i class="bi bi-truck me-2"></i>Shipping To</h2></div>
      <div class="panel-body">
        <address class="order-address mb-3">
          <strong><?= e($order['shipping_name']) ?></strong><br>
          <?= e($order['shipping_address']) ?><br>
          <?= e($order['shipping_city']) ?><?= $order['shipping_state'] ? ', ' . e($order['shipping_state']) : '' ?> <?= e($order['shipping_zip']) ?><br>
          <?= e($order['shipping_country']) ?><br>
          <span class="text-muted"><?= e($order['shipping_phone']) ?></span><br>
          <span class="text-muted"><?= e($order['shipping_email']) ?></span>
        </address>
        <hr>
        <p class="mb-0 small text-muted">Registered account: <strong><?= e($order['customer_name']) ?></strong><br><?= e($order['user_email']) ?></p>
      </div>
    </div>

    <div class="panel-card">
      <div class="panel-head"><h2><i class="bi bi-sliders me-2"></i>Manage Order</h2></div>
      <form method="post" action="<?= url('admin/order_view.php?id=' . $id) ?>" class="panel-body">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_status">
        <div class="mb-3">
          <label class="form-label lux-label" for="ovStatus">Order Status</label>
          <select id="ovStatus" name="status" class="form-select">
            <?php foreach (['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $s): ?>
              <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label lux-label" for="ovPayment">Payment Status</label>
          <select id="ovPayment" name="payment_status" class="form-select">
            <?php foreach (['Unpaid', 'Paid', 'Refunded'] as $ps): ?>
              <option value="<?= $ps ?>" <?= $order['payment_status'] === $ps ? 'selected' : '' ?>><?= $ps ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn lux-btn-gold w-100"><i class="bi bi-check2 me-2"></i>Save Changes</button>
      </form>
    </div>

    <a href="<?= url('admin/orders.php') ?>" class="btn btn-outline-secondary w-100 mt-3"><i class="bi bi-arrow-left me-2"></i>Back to Orders</a>
  </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
