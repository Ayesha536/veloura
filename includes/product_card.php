<?php
/**
 * VELOURA — Reusable product card.
 * Expects: $product (row from products table), optional $delay (stagger index)
 */
$p        = $product;
$price    = (float)$p['price'];
$sale     = ($p['sale_price'] !== null && $p['sale_price'] !== '' && (float)$p['sale_price'] > 0) ? (float)$p['sale_price'] : null;
$effPrice = $sale ?? $price;
$stock    = (int)$p['stock'];
$isNew    = !empty($p['created_at']) && (time() - strtotime($p['created_at'])) < 86400 * 21;
$delay    = isset($delay) ? (int)$delay % 4 : 0;
$inWishlist = !empty($inWishlistIds) && in_array((int)$p['id'], $inWishlistIds, true);
?>
<div class="col-6 col-md-4 col-lg-3">
  <article class="product-card reveal" style="--d:<?= $delay ?>">
    <div class="product-media">
      <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>">
        <img src="<?= url('assets/images/products/' . $p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
      </a>
      <div class="product-badges">
        <?php if ($sale): ?><span class="badge badge-sale">Sale</span><?php endif; ?>
        <?php if ($isNew && !$sale): ?><span class="badge badge-new">New</span><?php endif; ?>
        <?php if ($stock === 0): ?><span class="badge badge-out">Sold Out</span><?php endif; ?>
      </div>
      <div class="product-actions">
        <button type="button" class="p-action wishlist-btn<?= $inWishlist ? ' active' : '' ?>" data-product="<?= (int)$p['id'] ?>" title="Add to wishlist" <?= !is_logged_in() ? 'data-need-login="1"' : '' ?>>
          <i class="bi bi-<?= $inWishlist ? 'heart-fill' : 'heart' ?>"></i>
        </button>
        <button type="button" class="p-action add-cart-btn" data-product="<?= (int)$p['id'] ?>" title="Add to cart" <?= $stock === 0 ? 'disabled' : '' ?>>
          <i class="bi bi-bag-plus"></i>
        </button>
      </div>
    </div>
    <div class="product-body">
      <span class="product-cat"><?= e($p['category_name'] ?? '') ?></span>
      <h3 class="product-name"><a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>"><?= e($p['name']) ?></a></h3>
      <div class="product-rating">
        <?= stars_html((float)($p['rating'] ?? 5)) ?>
        <span class="rating-num"><?= number_format((float)($p['rating'] ?? 5), 1) ?></span>
      </div>
      <div class="product-price">
        <?php if ($sale): ?>
          <span class="price-sale"><?= money($sale) ?></span>
          <span class="price-old"><?= money($price) ?></span>
        <?php else: ?>
          <span class="price"><?= money($price) ?></span>
        <?php endif; ?>
      </div>
      <div class="product-stock">
        <?php if ($stock === 0): ?>
          <span class="stock-out"><i class="bi bi-x-circle"></i> Out of stock</span>
        <?php elseif ($stock <= 5): ?>
          <span class="stock-low"><i class="bi bi-exclamation-circle"></i> Only <?= $stock ?> left</span>
        <?php else: ?>
          <span class="stock-in"><i class="bi bi-check-circle"></i> In stock</span>
        <?php endif; ?>
      </div>
    </div>
  </article>
</div>
