<?php
/**
 * VELOURA — Checkout (requires login). Places order in a DB transaction with stock checks.
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Checkout';

require_login();

$user = current_user();
$items    = cart_items();
$subtotal = cart_subtotal();
$shipping = shipping_fee_for($subtotal);
$total    = $subtotal + $shipping;

// Empty cart -> back to shop
if (!$items) {
    set_flash('info', 'Your cart is empty — add a piece before checking out.');
    redirect(url('shop.php'));
}

$errors = [];
$old = [
    'name' => $user['name'], 'email' => $user['email'], 'phone' => $user['phone'] ?? '',
    'address' => '', 'city' => '', 'state' => '', 'zip' => '', 'country' => 'France',
    'notes' => '', 'payment_method' => 'cod',
];

// Prefill last used address if any
$stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([(int)$user['id']]);
if ($last = $stmt->fetch()) {
    $old['address'] = $last['shipping_address'];
    $old['city']    = $last['shipping_city'];
    $old['state']   = $last['shipping_state'];
    $old['zip']     = $last['shipping_zip'];
    $old['country'] = $last['shipping_country'] ?: 'France';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old['name']           = trim((string)($_POST['name'] ?? ''));
    $old['email']          = strtolower(trim((string)($_POST['email'] ?? '')));
    $old['phone']          = trim((string)($_POST['phone'] ?? ''));
    $old['address']        = trim((string)($_POST['address'] ?? ''));
    $old['city']           = trim((string)($_POST['city'] ?? ''));
    $old['state']          = trim((string)($_POST['state'] ?? ''));
    $old['zip']            = trim((string)($_POST['zip'] ?? ''));
    $old['country']        = trim((string)($_POST['country'] ?? ''));
    $old['notes']          = trim((string)($_POST['notes'] ?? ''));
    $old['payment_method'] = (string)($_POST['payment_method'] ?? 'cod');
    $cardName = trim((string)($_POST['card_name'] ?? ''));
    $cardNumber = preg_replace('/[\s-]/', '', (string)($_POST['card_number'] ?? ''));
    $cardExpiry = trim((string)($_POST['card_expiry'] ?? ''));
    $cardCvv = trim((string)($_POST['card_cvv'] ?? ''));

    // ---- Server-side validation ----
    if (mb_strlen($old['name']) < 2)      $errors['name'] = 'Full name is required.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required.';
    if (!preg_match('/^[0-9 +\-().]{6,20}$/', $old['phone'])) $errors['phone'] = 'Valid phone number is required.';
    if (mb_strlen($old['address']) < 5)   $errors['address'] = 'Please enter your street address.';
    if (mb_strlen($old['city']) < 2)      $errors['city'] = 'City is required.';
    if (mb_strlen($old['zip']) < 3)       $errors['zip'] = 'Postal code is required.';
    if (mb_strlen($old['country']) < 2)   $errors['country'] = 'Country is required.';
    if (!in_array($old['payment_method'], ['cod', 'card'], true)) $old['payment_method'] = 'cod';

    if ($old['payment_method'] === 'card') {
        if (mb_strlen($cardName) < 2) $errors['card'] = 'Name on card is required.';
        elseif (!preg_match('/^\d{13,19}$/', $cardNumber)) $errors['card'] = 'Card number must be 13–19 digits.';
        elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $cardExpiry)) $errors['card'] = 'Expiry must be in MM/YY format.';
        elseif (!preg_match('/^\d{3,4}$/', $cardCvv)) $errors['card'] = 'CVV must be 3–4 digits.';
    }

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            // Lock & re-verify stock for every line
            $orderItemsData = [];
            $orderSubtotal = 0.0;
            foreach ($items as $item) {
                $lockStmt = $pdo->prepare('SELECT id, name, price, sale_price, stock FROM products WHERE id = ? FOR UPDATE');
                $lockStmt->execute([(int)$item['product']['id']]);
                $row = $lockStmt->fetch();
                if (!$row || (int)$row['stock'] < $item['qty']) {
                    throw new RuntimeException('Insufficient stock for "' . ($row['name'] ?? $item['product']['name']) . '". Please adjust your cart.');
                }
                $unit = ($row['sale_price'] !== null && (float)$row['sale_price'] > 0) ? (float)$row['sale_price'] : (float)$row['price'];
                $orderItemsData[] = [
                    'product_id' => (int)$row['id'],
                    'name'       => $row['name'],
                    'price'      => $unit,
                    'qty'        => $item['qty'],
                ];
                $orderSubtotal += $unit * $item['qty'];
            }

            $orderShipping = shipping_fee_for($orderSubtotal);
            $orderTotal = $orderSubtotal + $orderShipping;
            $orderNumber = generate_order_number();

            // Insert order
            $insOrder = $pdo->prepare(
                "INSERT INTO orders (order_number, user_id, subtotal, shipping_fee, total, payment_method, payment_status, status,
                    shipping_name, shipping_email, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_zip, shipping_country, notes, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $insOrder->execute([
                $orderNumber, (int)$user['id'], $orderSubtotal, $orderShipping, $orderTotal,
                $old['payment_method'], $old['payment_method'] === 'card' ? 'Paid' : 'Unpaid',
                $old['name'], $old['email'], $old['phone'],
                $old['address'], $old['city'], $old['state'], $old['zip'], $old['country'], $old['notes'],
            ]);
            $orderId = (int)$pdo->lastInsertId();

            // Insert items + decrement stock
            $insItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity) VALUES (?, ?, ?, ?, ?)');
            $decStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            foreach ($orderItemsData as $d) {
                $insItem->execute([$orderId, $d['product_id'], $d['name'], $d['price'], $d['qty']]);
                $decStock->execute([$d['qty'], $d['product_id']]);
            }

            $pdo->commit();
            clear_cart();
            set_flash('success', 'Merci! Order ' . $orderNumber . ' has been placed successfully.');
            redirect(url('order_details.php?id=' . $orderId));

        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors['general'] = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors['general'] = 'Something went wrong while placing your order. Please try again.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb" class="lux-breadcrumb">
  <div class="container">
    <h1 class="page-title">Checkout</h1>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= url('cart.php') ?>">Cart</a></li>
      <li class="breadcrumb-item active" aria-current="page">Checkout</li>
    </ol>
  </div>
</nav>

<section class="section-pad pt-4">
  <div class="container">
    <?php if (isset($errors['general'])): ?>
      <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= url('checkout.php') ?>" id="checkoutForm" novalidate>
      <?= csrf_field() ?>
      <div class="row g-4">
        <!-- Shipping details -->
        <div class="col-lg-7">
          <div class="lux-card p-4 p-md-5 mb-4">
            <h3 class="summary-title mb-4"><i class="bi bi-truck me-2"></i>Shipping Details</h3>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label lux-label" for="coName">Full Name *</label>
                <input type="text" id="coName" name="name" class="form-control lux-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= e($old['name']) ?>" required>
                <div class="invalid-feedback"><?= e($errors['name'] ?? '') ?></div>
              </div>
              <div class="col-md-6">
                <label class="form-label lux-label" for="coEmail">Email *</label>
                <input type="email" id="coEmail" name="email" class="form-control lux-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= e($old['email']) ?>" required>
                <div class="invalid-feedback"><?= e($errors['email'] ?? '') ?></div>
              </div>
              <div class="col-md-6">
                <label class="form-label lux-label" for="coPhone">Phone *</label>
                <input type="tel" id="coPhone" name="phone" class="form-control lux-input <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" value="<?= e($old['phone']) ?>" required>
                <div class="invalid-feedback"><?= e($errors['phone'] ?? '') ?></div>
              </div>
              <div class="col-md-6">
                <label class="form-label lux-label" for="coCountry">Country *</label>
                <input type="text" id="coCountry" name="country" class="form-control lux-input <?= isset($errors['country']) ? 'is-invalid' : '' ?>" value="<?= e($old['country']) ?>" required>
                <div class="invalid-feedback"><?= e($errors['country'] ?? '') ?></div>
              </div>
              <div class="col-12">
                <label class="form-label lux-label" for="coAddress">Street Address *</label>
                <input type="text" id="coAddress" name="address" class="form-control lux-input <?= isset($errors['address']) ? 'is-invalid' : '' ?>" value="<?= e($old['address']) ?>" required placeholder="Apartment, suite, floor…">
                <div class="invalid-feedback"><?= e($errors['address'] ?? '') ?></div>
              </div>
              <div class="col-md-4">
                <label class="form-label lux-label" for="coCity">City *</label>
                <input type="text" id="coCity" name="city" class="form-control lux-input <?= isset($errors['city']) ? 'is-invalid' : '' ?>" value="<?= e($old['city']) ?>" required>
                <div class="invalid-feedback"><?= e($errors['city'] ?? '') ?></div>
              </div>
              <div class="col-md-4">
                <label class="form-label lux-label" for="coState">State / Region</label>
                <input type="text" id="coState" name="state" class="form-control lux-input" value="<?= e($old['state']) ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label lux-label" for="coZip">Postal Code *</label>
                <input type="text" id="coZip" name="zip" class="form-control lux-input <?= isset($errors['zip']) ? 'is-invalid' : '' ?>" value="<?= e($old['zip']) ?>" required>
                <div class="invalid-feedback"><?= e($errors['zip'] ?? '') ?></div>
              </div>
              <div class="col-12">
                <label class="form-label lux-label" for="coNotes">Order Notes <span class="text-muted">(optional — gift message, delivery preferences)</span></label>
                <textarea id="coNotes" name="notes" rows="3" class="form-control lux-input"><?= e($old['notes']) ?></textarea>
              </div>
            </div>
          </div>

          <!-- Payment -->
          <div class="lux-card p-4 p-md-5">
            <h3 class="summary-title mb-4"><i class="bi bi-credit-card me-2"></i>Payment Method</h3>
            <div class="payment-option">
              <input type="radio" name="payment_method" value="cod" id="payCod" <?= $old['payment_method'] === 'cod' ? 'checked' : '' ?>>
              <label for="payCod">
                <span class="pay-radio-title"><i class="bi bi-cash-coin me-2"></i>Cash on Delivery</span>
                <span class="pay-radio-desc">Pay in cash when your Veloura box arrives.</span>
              </label>
            </div>
            <div class="payment-option">
              <input type="radio" name="payment_method" value="card" id="payCard" <?= $old['payment_method'] === 'card' ? 'checked' : '' ?>>
              <label for="payCard">
                <span class="pay-radio-title"><i class="bi bi-credit-card-2-front me-2"></i>Credit / Debit Card</span>
                <span class="pay-radio-desc">Demo gateway — no real charge is made.</span>
              </label>
            </div>
            <div class="card-fields mt-3 <?= $old['payment_method'] === 'card' ? '' : 'd-none' ?>" id="cardFields">
              <div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i>This is a demonstration checkout — card details are validated but never stored or charged.</div>
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label lux-label" for="cardName">Name on Card</label>
                  <input type="text" id="cardName" name="card_name" class="form-control lux-input" value="<?= e($_POST['card_name'] ?? '') ?>" autocomplete="cc-name">
                </div>
                <div class="col-12">
                  <label class="form-label lux-label" for="cardNumber">Card Number</label>
                  <input type="text" id="cardNumber" name="card_number" class="form-control lux-input" value="<?= e($_POST['card_number'] ?? '') ?>" placeholder="4242 4242 4242 4242" autocomplete="cc-number" inputmode="numeric">
                </div>
                <div class="col-6">
                  <label class="form-label lux-label" for="cardExpiry">Expiry (MM/YY)</label>
                  <input type="text" id="cardExpiry" name="card_expiry" class="form-control lux-input" value="<?= e($_POST['card_expiry'] ?? '') ?>" placeholder="12/28" autocomplete="cc-exp">
                </div>
                <div class="col-6">
                  <label class="form-label lux-label" for="cardCvv">CVV</label>
                  <input type="password" id="cardCvv" name="card_cvv" class="form-control lux-input" value="<?= e($_POST['card_cvv'] ?? '') ?>" placeholder="123" autocomplete="cc-csc" inputmode="numeric">
                </div>
              </div>
              <?php if (isset($errors['card'])): ?><div class="text-danger small mt-2"><?= e($errors['card']) ?></div><?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Summary -->
        <div class="col-lg-5">
          <div class="lux-card p-4 summary-card sticky-lg-top">
            <h3 class="summary-title">Your Order</h3>
            <?php foreach ($items as $item): $p = $item['product']; ?>
              <div class="mini-item">
                <img src="<?= url('assets/images/products/' . e($p['image'])) ?>" alt="<?= e($p['name']) ?>">
                <div class="flex-grow-1">
                  <p class="mb-0 fw-medium"><?= e($p['name']) ?></p>
                  <small class="text-muted">Qty <?= $item['qty'] ?> × <?= money($item['price']) ?></small>
                </div>
                <strong><?= money($item['line_total']) ?></strong>
              </div>
            <?php endforeach; ?>
            <div class="summary-divider"></div>
            <div class="summary-row"><span>Subtotal</span><strong><?= money($subtotal) ?></strong></div>
            <div class="summary-row"><span>Shipping</span><strong><?= $shipping > 0 ? money($shipping) : '<span class="text-success">Free</span>' ?></strong></div>
            <div class="summary-row total"><span>Total</span><strong><?= money($total) ?></strong></div>
            <button type="submit" class="btn lux-btn-gold w-100 mt-3 py-3" id="placeOrderBtn">
              Place Order <i class="bi bi-gem ms-2"></i>
            </button>
            <p class="secure-note"><i class="bi bi-shield-lock me-1"></i> SSL secured · 30-day returns · Signature gift box</p>
          </div>
        </div>
      </div>
    </form>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
