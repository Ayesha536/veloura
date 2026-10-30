<?php
/**
 * VELOURA — Product details page.
 */
require_once __DIR__ . '/config/config.php';

$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    set_flash('error', 'Product not found.');
    redirect(url('shop.php'));
}

$stmt = db()->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug
     FROM products p LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.slug = ? AND p.is_active = 1 LIMIT 1"
);
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('error', 'This piece is no longer available.');
    redirect(url('shop.php'));
}

$pageTitle = $product['name'];
$price    = (float)$product['price'];
$sale     = ($product['sale_price'] !== null && $product['sale_price'] !== '' && (float)$product['sale_price'] > 0) ? (float)$product['sale_price'] : null;
$effPrice = $sale ?? $price;
$stock    = (int)$product['stock'];

// Gallery (main image + optional extras in `gallery` column, comma separated)
$gallery = [];
if (!empty($product['image'])) {
    $gallery[] = $product['image'];
}
if (!empty($product['gallery'])) {
    foreach (explode(',', $product['gallery']) as $g) {
        $g = trim($g);
        if ($g !== '' && !in_array($g, $gallery, true)) $gallery[] = $g;
    }
}

// Related products
$relStmt = db()->prepare(
    "SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 AND p.category_id = ? AND p.id != ?
     ORDER BY RAND() LIMIT 4"
);
$relStmt->execute([(int)$product['category_id'], (int)$product['id']]);
$related = $relStmt->fetchAll();

$inWishlistIds = [];
if (is_logged_in()) {
    $stmt = db()->prepare('SELECT product_id FROM wishlist WHERE user_id = ?');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $inWishlistIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}
$inWishlist = in_array((int)$product['id'], $inWishlistIds, true);

include __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb" class="lux-breadcrumb">
  <div class="container">
    <ol class="breadcrumb mb-0 pt-3">
      <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
      <li class="breadcrumb-item"><a href="<?= url('shop.php') ?>">Shop</a></li>
      <?php if ($product['category_slug']): ?>
        <li class="breadcrumb-item"><a href="<?= url('shop.php?category=' . urlencode($product['category_slug'])) ?>"><?= e($product['category_name']) ?></a></li>
      <?php endif; ?>
      <li class="breadcrumb-item active" aria-current="page"><?= e($product['name']) ?></li>
    </ol>
  </div>
</nav>

<section class="section-pad pt-4">
  <div class="container">
    <div class="row g-5">
      <!-- Gallery -->
      <div class="col-lg-6">
        <div class="product-gallery">
          <div class="gallery-main">
            <img src="<?= url('assets/images/products/' . e($gallery[0])) ?>" alt="<?= e($product['name']) ?>" id="galleryMain">
            <?php if ($sale): ?><span class="badge badge-sale gallery-badge">Save <?= money($price - $sale) ?></span><?php endif; ?>
          </div>
          <?php if (count($gallery) > 1): ?>
            <div class="gallery-thumbs">
              <?php foreach ($gallery as $i => $g): ?>
                <button type="button" class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>" data-src="<?= url('assets/images/products/' . e($g)) ?>">
                  <img src="<?= url('assets/images/products/' . e($g)) ?>" alt="View <?= $i + 1 ?>">
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Details -->
      <div class="col-lg-6">
        <div class="product-detail">
          <?php if ($product['category_name']): ?>
            <a class="product-cat-lg" href="<?= url('shop.php?category=' . urlencode($product['category_slug'])) ?>"><?= e($product['category_name']) ?></a>
          <?php endif; ?>
          <h1 class="detail-title"><?= e($product['name']) ?></h1>

          <div class="detail-rating">
            <?= stars_html((float)$product['rating']) ?>
            <span><?= number_format((float)$product['rating'], 1) ?></span>
            <span class="text-muted">(<?= (int)$product['reviews_count'] ?> reviews)</span>
          </div>

          <div class="detail-price">
            <?php if ($sale): ?>
              <span class="price-sale"><?= money($sale) ?></span>
              <span class="price-old"><?= money($price) ?></span>
              <span class="badge badge-sale ms-1">Save <?= number_format(100 - ($sale / $price * 100), 0) ?>%</span>
            <?php else: ?>
              <span class="price"><?= money($price) ?></span>
            <?php endif; ?>
          </div>

          <?php if ($product['short_description']): ?>
            <p class="detail-short"><?= e($product['short_description']) ?></p>
          <?php endif; ?>

          <div class="detail-stock">
            <?php if ($stock === 0): ?>
              <span class="stock-out"><i class="bi bi-x-circle"></i> Out of stock — check back soon</span>
            <?php elseif ($stock <= 5): ?>
              <span class="stock-low"><i class="bi bi-exclamation-circle"></i> Only <?= $stock ?> left in stock</span>
            <?php else: ?>
              <span class="stock-in"><i class="bi bi-check-circle"></i> In stock — ready to ship</span>
            <?php endif; ?>
          </div>

          <form class="detail-actions" id="detailAddForm" data-product="<?= (int)$product['id'] ?>">
            <div class="qty-selector" role="group" aria-label="Quantity">
              <button type="button" class="qty-btn" data-delta="-1" aria-label="Decrease">−</button>
              <input type="number" class="qty-input" id="qtyInput" name="qty" value="1" min="1" max="<?= max(1, $stock) ?>" <?= $stock === 0 ? 'disabled' : '' ?>>
              <button type="button" class="qty-btn" data-delta="1" aria-label="Increase">+</button>
            </div>
            <button type="button" class="btn lux-btn add-cart-detail" data-product="<?= (int)$product['id'] ?>" <?= $stock === 0 ? 'disabled' : '' ?>>
              <i class="bi bi-bag-plus me-2"></i>Add to Cart
            </button>
            <button type="button" class="btn lux-btn-ghost wishlist-btn<?= $inWishlist ? ' active' : '' ?>" data-product="<?= (int)$product['id'] ?>" <?= !is_logged_in() ? 'data-need-login="1"' : '' ?>>
              <i class="bi bi-<?= $inWishlist ? 'heart-fill' : 'heart' ?> me-1"></i><?= $inWishlist ? 'In Wishlist' : 'Wishlist' ?>
            </button>
          </form>

          <a href="<?= url('checkout.php') ?>" class="btn lux-btn-gold w-100 mt-3 buy-now-btn" data-product="<?= (int)$product['id'] ?>" <?= $stock === 0 ? 'style="display:none"' : '' ?>>
            Buy Now <i class="bi bi-lightning-charge ms-2"></i>
          </a>

          <ul class="detail-meta">
            <?php if ($product['sku']): ?><li><span>SKU</span> <?= e($product['sku']) ?></li><?php endif; ?>
            <?php if ($product['material']): ?><li><span>Material</span> <?= e($product['material']) ?></li><?php endif; ?>
            <li><span>Category</span> <?= e($product['category_name'] ?? 'Accessories') ?></li>
            <li><span>Shipping</span> Free over <?= money((float)get_setting('free_shipping_threshold', '150')) ?> · 30-day returns</li>
          </ul>

          <div class="detail-trust">
            <div class="trust-item"><i class="bi bi-gift"></i> Signature lavender gift box</div>
            <div class="trust-item"><i class="bi bi-patch-check"></i> Authenticated quality</div>
            <div class="trust-item"><i class="bi bi-arrow-repeat"></i> 30-day easy returns</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="detail-tabs mt-5">
      <ul class="nav lux-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabDesc" type="button" role="tab">Description</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDetails" type="button" role="tab">Details &amp; Care</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabShip" type="button" role="tab">Shipping &amp; Returns</button></li>
      </ul>
      <div class="tab-content lux-tab-content">
        <div class="tab-pane fade show active" id="tabDesc" role="tabpanel">
          <p><?= nl2br(e($product['description'] ?: $product['short_description'])) ?></p>
        </div>
        <div class="tab-pane fade" id="tabDetails" role="tabpanel">
          <ul class="care-list">
            <li><strong>Material:</strong> <?= e($product['material'] ?: 'Premium alloy with protective finish') ?></li>
            <li><strong>Care:</strong> Store in the provided pouch; avoid perfume, water and direct sunlight.</li>
            <li><strong>Cleaning:</strong> Polish gently with the included Veloura silver cloth.</li>
            <li><strong>SKU:</strong> <?= e($product['sku'] ?: '—') ?></li>
          </ul>
        </div>
        <div class="tab-pane fade" id="tabShip" role="tabpanel">
          <ul class="care-list">
            <li><strong>Dispatch:</strong> Orders ship within 1–2 business days from our atelier.</li>
            <li><strong>Standard shipping:</strong> <?= money((float)get_setting('shipping_fee', '12')) ?> — free on orders over <?= money((float)get_setting('free_shipping_threshold', '150')) ?>.</li>
            <li><strong>Returns:</strong> 30-day returns, unworn pieces in original packaging.</li>
            <li><strong>Gift wrap:</strong> Every Veloura order arrives in our signature lavender box at no extra cost.</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Related -->
    <?php if ($related): ?>
      <div class="section-head mt-5 pt-4">
        <p class="section-eyebrow">Complete the Look</p>
        <h2 class="section-title">You May <em>Also Love</em></h2>
      </div>
      <div class="row g-4">
        <?php foreach ($related as $i => $product2): $product = $product2; include __DIR__ . '/includes/product_card.php'; endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
