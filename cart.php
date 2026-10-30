<?php
/**
 * VELOURA — Shopping cart page (session cart, server-validated against DB).
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Shopping Cart';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update') {
        foreach ((array)($_POST['qty'] ?? []) as $pid => $qty) {
            $pid = (int)$pid;
            $qty = (int)$qty;
            if ($pid > 0) {
                // Clamp to available stock
                $stmt = db()->prepare('SELECT stock FROM products WHERE id = ? AND is_active = 1');
                $stmt->execute([$pid]);
                $row = $stmt->fetch();
                if ($row) {
                    update_cart($pid, min($qty, (int)$row['stock']));
                } else {
                    remove_from_cart($pid);
                }
            }
        }
        set_flash('success', 'Your cart has been updated.');
        redirect(url('cart.php'));
    }

    if ($action === 'remove') {
        $pid = (int)($_POST['product_id'] ?? 0);
        remove_from_cart($pid);
        set_flash('success', 'Item removed from your cart.');
        redirect(url('cart.php'));
    }

    if ($action === 'clear') {
        clear_cart();
        set_flash('success', 'Your cart is now empty.');
        redirect(url('cart.php'));
    }
}

$items     = cart_items();
$subtotal  = cart_subtotal();
$shipping  = shipping_fee_for($subtotal);
$total     = $subtotal + $shipping;
$freeThreshold = (float)get_setting('free_shipping_threshold', '150');

include __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb" class="lux-breadcrumb">
  <div class="container">
    <h1 class="page-title">Shopping Cart</h1>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Cart (<?= cart_count() ?> items)</li>
    </ol>
  </div>
</nav>

<section class="section-pad pt-4">
  <div class="container">
    <?php if (!$items): ?>
      <div class="empty-state text-center py-5">
        <i class="bi bi-bag"></i>
        <h3>Your cart is empty</h3>
        <p class="text-muted">Discover pieces you'll love in our curated collections.</p>
        <a href="<?= url('shop.php') ?>" class="btn lux-btn mt-2">Start Shopping <i class="bi bi-arrow-right ms-2"></i></a>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="lux-card">
            <?php foreach ($items as $item): $p = $item['product']; ?>
              <div class="cart-row">
                <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>" class="cart-img">
                  <img src="<?= url('assets/images/products/' . e($p['image'])) ?>" alt="<?= e($p['name']) ?>">
                </a>
                <div class="cart-info">
                  <span class="product-cat"><?= e($p['category_name'] ?? '') ?></span>
                  <h3><a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>"><?= e($p['name']) ?></a></h3>
                  <p class="cart-price"><?= money($item['price']) ?> each</p>
                  <?php if ($item['over_stock']): ?>
                    <p class="stock-low mb-0"><i class="bi bi-exclamation-circle"></i> Only <?= (int)$p['stock'] ?> available — please adjust quantity</p>
                  <?php endif; ?>
                </div>
                <form method="post" action="<?= url('cart.php') ?>" class="cart-qty-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update">
                  <div class="qty-selector" role="group" aria-label="Quantity">
                    <button type="button" class="qty-btn" data-delta="-1" aria-label="Decrease">−</button>
                    <input type="number" class="qty-input cart-qty-input" name="qty[<?= (int)$p['id'] ?>]" data-product="<?= (int)$p['id'] ?>" value="<?= $item['qty'] ?>" min="1" max="<?= max(1, (int)$p['stock']) ?>" <?= $item['over_stock'] ? 'max="' . (int)$p['stock'] . '"' : '' ?>>
                    <button type="button" class="qty-btn" data-delta="1" aria-label="Increase">+</button>
                    <button type="submit" class="qty-btn ms-1 text-muted" title="Update quantity" aria-label="Update quantity"><i class="bi bi-arrow-repeat small"></i></button>
                  </div>
                </form>
                <div class="cart-line-total"><?= money($item['line_total']) ?></div>
                <form method="post" action="<?= url('cart.php') ?>" class="cart-remove-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                  <button type="submit" class="cart-remove" aria-label="Remove <?= e($p['name']) ?>"><i class="bi bi-x-lg"></i></button>
                </form>
              </div>
            <?php endforeach; ?>
            <div class="cart-footer">
              <a href="<?= url('shop.php') ?>" class="lux-link"><i class="bi bi-arrow-left me-1"></i> Continue shopping</a>
              <form method="post" action="<?= url('cart.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn lux-btn-ghost btn-sm">Clear Cart</button>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="lux-card p-4 summary-card">
            <h3 class="summary-title">Order Summary</h3>
            <div class="summary-row"><span>Subtotal</span><strong><?= money($subtotal) ?></strong></div>
            <div class="summary-row">
              <span>Shipping</span>
              <strong><?= $shipping > 0 ? money($shipping) : '<span class="text-success">Free</span>' ?></strong>
            </div>
            <?php if ($shipping > 0): ?>
              <p class="free-ship-hint">Add <?= money($freeThreshold - $subtotal) ?> more for <strong>free shipping</strong></p>
            <?php endif; ?>
            <div class="summary-divider"></div>
            <div class="summary-row total"><span>Total</span><strong><?= money($total) ?></strong></div>
            <a href="<?= url('checkout.php') ?>" class="btn lux-btn-gold w-100 mt-3 py-3">Proceed to Checkout <i class="bi bi-arrow-right ms-2"></i></a>
            <p class="secure-note"><i class="bi bi-shield-lock me-1"></i> Secure, encrypted checkout</p>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
