<?php
/**
 * VELOURA — Admin: add / edit product with image upload & server-side validation.
 */
require_once __DIR__ . '/../config/config.php';
require_admin();
$activeNav = 'product_form';

$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

$product = [
    'name' => '', 'slug' => '', 'category_id' => '', 'price' => '', 'sale_price' => '',
    'stock' => 10, 'sku' => '', 'short_description' => '', 'description' => '',
    'material' => '', 'image' => '', 'gallery' => '', 'is_featured' => 0, 'is_active' => 1,
    'rating' => 5.0, 'reviews_count' => 0,
];
$errors = [];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        set_flash('error', 'Product not found.');
        redirect(url('admin/products.php'));
    }
    $product = array_merge($product, $existing);
}

$categories = db()->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $product['name']              = trim((string)($_POST['name'] ?? ''));
    $product['slug']              = trim((string)($_POST['slug'] ?? ''));
    $product['category_id']       = (int)($_POST['category_id'] ?? 0);
    $product['price']             = (string)($_POST['price'] ?? '');
    $product['sale_price']        = trim((string)($_POST['sale_price'] ?? ''));
    $product['stock']             = (int)($_POST['stock'] ?? 0);
    $product['sku']               = trim((string)($_POST['sku'] ?? ''));
    $product['short_description'] = trim((string)($_POST['short_description'] ?? ''));
    $product['description']       = trim((string)($_POST['description'] ?? ''));
    $product['material']          = trim((string)($_POST['material'] ?? ''));
    $product['is_featured']       = isset($_POST['is_featured']) ? 1 : 0;
    $product['is_active']         = isset($_POST['is_active']) ? 1 : 0;
    $product['rating']            = min(5, max(0, (float)($_POST['rating'] ?? 5)));
    $product['reviews_count']     = max(0, (int)($_POST['reviews_count'] ?? 0));

    // ---- Validation ----
    if (mb_strlen($product['name']) < 2 || mb_strlen($product['name']) > 150) {
        $errors['name'] = 'Name must be 2–150 characters.';
    }
    $slug = $product['slug'] !== '' ? slugify($product['slug']) : slugify($product['name']);
    $stmt = db()->prepare('SELECT id FROM products WHERE slug = ? AND id != ?');
    $stmt->execute([$slug, $id]);
    if ($stmt->fetch()) {
        $slug .= '-' . bin2hex(random_bytes(2));
    }
    if ($product['category_id'] <= 0) {
        $errors['category_id'] = 'Please choose a category.';
    } else {
        $chk = db()->prepare('SELECT id FROM categories WHERE id = ?');
        $chk->execute([$product['category_id']]);
        if (!$chk->fetch()) $errors['category_id'] = 'Invalid category.';
    }
    if (!is_numeric($product['price']) || (float)$product['price'] <= 0) {
        $errors['price'] = 'Enter a valid price greater than 0.';
    }
    if ($product['sale_price'] !== '' && (!is_numeric($product['sale_price']) || (float)$product['sale_price'] <= 0 || (float)$product['sale_price'] >= (float)$product['price'])) {
        $errors['sale_price'] = 'Sale price must be a positive number lower than the regular price.';
    }
    if ($product['stock'] < 0 || $product['stock'] > 1000000) {
        $errors['stock'] = 'Stock must be 0 or more.';
    }

    // ---- Image upload ----
    $newImage = upload_image($_FILES['image'] ?? null, ROOT_PATH . '/assets/images/products', 'prod');
    if (!$isEdit && !$newImage) {
        $errors['image'] = 'A product image is required.';
    }

    if (!$errors) {
        if ($newImage) {
            $product['image'] = $newImage;
        }
        if ($isEdit) {
            $stmt = db()->prepare(
                "UPDATE products SET name=?, slug=?, category_id=?, price=?, sale_price=?, stock=?, sku=?,
                 short_description=?, description=?, material=?, image=?, is_featured=?, is_active=?, rating=?, reviews_count=?
                 WHERE id=?"
            );
            $stmt->execute([
                $product['name'], $slug, $product['category_id'], (float)$product['price'],
                $product['sale_price'] !== '' ? (float)$product['sale_price'] : null,
                $product['stock'], $product['sku'], $product['short_description'], $product['description'],
                $product['material'], $product['image'], $product['is_featured'], $product['is_active'],
                $product['rating'], $product['reviews_count'], $id,
            ]);
            set_flash('success', 'Product "' . $product['name'] . '" updated successfully.');
        } else {
            $stmt = db()->prepare(
                "INSERT INTO products (name, slug, category_id, price, sale_price, stock, sku, short_description, description,
                 material, image, gallery, is_featured, is_active, rating, reviews_count, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())"
            );
            $stmt->execute([
                $product['name'], $slug, $product['category_id'], (float)$product['price'],
                $product['sale_price'] !== '' ? (float)$product['sale_price'] : null,
                $product['stock'], $product['sku'], $product['short_description'], $product['description'],
                $product['material'], $product['image'], '', $product['is_featured'], $product['is_active'],
                $product['rating'], $product['reviews_count'],
            ]);
            $id = (int)db()->lastInsertId();
            set_flash('success', 'Product "' . $product['name'] . '" created successfully.');
        }
        redirect(url('admin/products.php'));
    }
}

$pageTitle = $isEdit ? 'Edit Product' : 'Add Product';
include __DIR__ . '/../includes/admin_header.php';
?>

<form method="post" action="<?= url('admin/product_form.php' . ($isEdit ? '?id=' . $id : '')) ?>" enctype="multipart/form-data" class="product-form">
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="panel-card">
        <div class="panel-head"><h2><i class="bi bi-gem me-2"></i>Product Information</h2></div>
        <div class="panel-body">
          <div class="mb-3">
            <label class="form-label lux-label" for="pfName">Product Name *</label>
            <input type="text" id="pfName" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= e($product['name']) ?>" required>
            <div class="invalid-feedback"><?= e($errors['name'] ?? '') ?></div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label lux-label" for="pfSlug">URL Slug <span class="text-muted">(auto if empty)</span></label>
              <input type="text" id="pfSlug" name="slug" class="form-control" value="<?= e($product['slug']) ?>" placeholder="aurelia-pearl-earrings">
            </div>
            <div class="col-md-6">
              <label class="form-label lux-label" for="pfSku">SKU</label>
              <input type="text" id="pfSku" name="sku" class="form-control" value="<?= e($product['sku']) ?>" placeholder="VEL-EAR-001">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label lux-label" for="pfShort">Short Description <span class="text-muted">(shown on product page)</span></label>
            <textarea id="pfShort" name="short_description" rows="2" class="form-control"><?= e($product['short_description']) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label lux-label" for="pfDesc">Full Description *</label>
            <textarea id="pfDesc" name="description" rows="6" class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"><?= e($product['description']) ?></textarea>
            <div class="invalid-feedback"><?= e($errors['description'] ?? '') ?></div>
          </div>
          <div class="mb-0">
            <label class="form-label lux-label" for="pfMaterial">Material</label>
            <input type="text" id="pfMaterial" name="material" class="form-control" value="<?= e($product['material']) ?>" placeholder="925 sterling silver, freshwater pearl">
          </div>
        </div>
      </div>

      <div class="panel-card mt-4">
        <div class="panel-head"><h2><i class="bi bi-camera me-2"></i>Image</h2></div>
        <div class="panel-body">
          <div class="row align-items-center g-3">
            <div class="col-auto">
              <?php if ($product['image']): ?>
                <img src="<?= url('assets/images/products/' . e($product['image'])) ?>" class="form-img-preview" alt="Current image" id="imgPreview">
              <?php else: ?>
                <div class="form-img-preview form-img-empty" id="imgPreview"><i class="bi bi-image"></i></div>
              <?php endif; ?>
            </div>
            <div class="col">
              <label class="form-label lux-label" for="pfImage"><?= $isEdit ? 'Replace Image' : 'Product Image *' ?></label>
              <input type="file" id="pfImage" name="image" class="form-control <?= isset($errors['image']) ? 'is-invalid' : '' ?>" accept="image/jpeg,image/png,image/webp,image/gif">
              <div class="invalid-feedback"><?= e($errors['image'] ?? '') ?></div>
              <div class="form-text">JPG, PNG, WEBP or GIF · max 4 MB · square images look best.</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="panel-card">
        <div class="panel-head"><h2><i class="bi bi-tags me-2"></i>Organization</h2></div>
        <div class="panel-body">
          <div class="mb-3">
            <label class="form-label lux-label" for="pfCategory">Category *</label>
            <select id="pfCategory" name="category_id" class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" required>
              <option value="">Choose category…</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)$product['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback"><?= e($errors['category_id'] ?? '') ?></div>
          </div>
          <div class="mb-3">
            <label class="form-label lux-label" for="pfPrice">Regular Price (<?= CURRENCY_SYMBOL ?>) *</label>
            <input type="number" step="0.01" min="0" id="pfPrice" name="price" class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>" value="<?= e($product['price']) ?>" required>
            <div class="invalid-feedback"><?= e($errors['price'] ?? '') ?></div>
          </div>
          <div class="mb-3">
            <label class="form-label lux-label" for="pfSale">Sale Price <span class="text-muted">(optional)</span></label>
            <input type="number" step="0.01" min="0" id="pfSale" name="sale_price" class="form-control <?= isset($errors['sale_price']) ? 'is-invalid' : '' ?>" value="<?= e($product['sale_price']) ?>">
            <div class="invalid-feedback"><?= e($errors['sale_price'] ?? '') ?></div>
          </div>
          <div class="mb-3">
            <label class="form-label lux-label" for="pfStock">Stock Quantity *</label>
            <input type="number" min="0" id="pfStock" name="stock" class="form-control <?= isset($errors['stock']) ? 'is-invalid' : '' ?>" value="<?= (int)$product['stock'] ?>" required>
            <div class="invalid-feedback"><?= e($errors['stock'] ?? '') ?></div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label lux-label" for="pfRating">Rating (0–5)</label>
              <input type="number" step="0.1" min="0" max="5" id="pfRating" name="rating" class="form-control" value="<?= e($product['rating']) ?>">
            </div>
            <div class="col-6">
              <label class="form-label lux-label" for="pfReviews">Reviews #</label>
              <input type="number" min="0" id="pfReviews" name="reviews_count" class="form-control" value="<?= (int)$product['reviews_count'] ?>">
            </div>
          </div>
          <div class="form-check lux-switch mb-2">
            <input class="form-check-input" type="checkbox" id="pfFeatured" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="pfFeatured">Featured on homepage</label>
          </div>
          <div class="form-check lux-switch">
            <input class="form-check-input" type="checkbox" id="pfActive" name="is_active" value="1" <?= $product['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="pfActive">Visible in store</label>
          </div>
        </div>
      </div>

      <div class="d-grid gap-2 mt-4">
        <button type="submit" class="btn lux-btn-gold py-3"><i class="bi bi-check2 me-2"></i><?= $isEdit ? 'Update Product' : 'Create Product' ?></button>
        <a href="<?= url('admin/products.php') ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </div>
  </div>
</form>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
