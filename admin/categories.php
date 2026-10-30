<?php
/**
 * VELOURA — Admin: category CRUD (add / edit / delete on one page).
 */
require_once __DIR__ . '/../config/config.php';
require_admin();
$pageTitle = 'Categories';
$activeNav = 'categories';

$editCategory = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $slugInput = trim((string)($_POST['slug'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        $sort = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
            set_flash('error', 'Category name must be 2–80 characters.');
            redirect(url('admin/categories.php'));
        }
        $slug = $slugInput !== '' ? slugify($slugInput) : slugify($name);
        $stmt = db()->prepare('SELECT id FROM categories WHERE slug = ? AND id != ?');
        $stmt->execute([$slug, $id]);
        if ($stmt->fetch()) $slug .= '-' . bin2hex(random_bytes(2));

        $newImage = upload_image($_FILES['image'] ?? null, ROOT_PATH . '/assets/images/products', 'cat');

        if ($id > 0) {
            if ($newImage) {
                db()->prepare('UPDATE categories SET name=?, slug=?, description=?, image=?, sort_order=?, is_active=? WHERE id=?')
                    ->execute([$name, $slug, $desc, $newImage, $sort, $isActive, $id]);
            } else {
                db()->prepare('UPDATE categories SET name=?, slug=?, description=?, sort_order=?, is_active=? WHERE id=?')
                    ->execute([$name, $slug, $desc, $sort, $isActive, $id]);
            }
            set_flash('success', 'Category "' . $name . '" updated.');
        } else {
            if (!$newImage) {
                set_flash('error', 'A category image is required for new categories.');
                redirect(url('admin/categories.php'));
            }
            db()->prepare('INSERT INTO categories (name, slug, description, image, sort_order, is_active) VALUES (?,?,?,?,?,?)')
                ->execute([$name, $slug, $desc, $newImage, $sort, $isActive]);
            set_flash('success', 'Category "' . $name . '" created.');
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $chk = db()->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $chk->execute([$id]);
        if ((int)$chk->fetchColumn() > 0) {
            set_flash('error', 'Cannot delete: this category still has products. Move or delete them first.');
        } else {
            db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            set_flash('success', 'Category deleted.');
        }
    }

    redirect(url('admin/categories.php'));
}

// Edit target
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editCategory = $stmt->fetch() ?: null;
}

$categories = get_categories(false);
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="row g-4">
  <!-- Form -->
  <div class="col-lg-4">
    <div class="panel-card">
      <div class="panel-head">
        <h2><i class="bi bi-<?= $editCategory ? 'pencil' : 'plus-lg' ?> me-2"></i><?= $editCategory ? 'Edit Category' : 'Add Category' ?></h2>
      </div>
      <form method="post" action="<?= url('admin/categories.php') ?>" enctype="multipart/form-data" class="panel-body">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editCategory['id'] ?? 0) ?>">

        <div class="mb-3">
          <label class="form-label lux-label" for="catName">Name *</label>
          <input type="text" id="catName" name="name" class="form-control" value="<?= e($editCategory['name'] ?? '') ?>" required maxlength="80">
        </div>
        <div class="mb-3">
          <label class="form-label lux-label" for="catSlug">Slug <span class="text-muted">(auto if empty)</span></label>
          <input type="text" id="catSlug" name="slug" class="form-control" value="<?= e($editCategory['slug'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label lux-label" for="catDesc">Description</label>
          <textarea id="catDesc" name="description" rows="3" class="form-control"><?= e($editCategory['description'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label lux-label" for="catImage"><?= $editCategory ? 'Replace Image (optional)' : 'Image *' ?></label>
          <input type="file" id="catImage" name="image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" <?= $editCategory ? '' : 'required' ?>>
        </div>
        <div class="mb-3">
          <label class="form-label lux-label" for="catSort">Sort Order</label>
          <input type="number" id="catSort" name="sort_order" class="form-control" value="<?= (int)($editCategory['sort_order'] ?? 0) ?>">
        </div>
        <div class="form-check lux-switch mb-3">
          <input class="form-check-input" type="checkbox" id="catActive" name="is_active" value="1" <?= ($editCategory === null || (int)$editCategory['is_active'] === 1) ? 'checked' : '' ?>>
          <label class="form-check-label" for="catActive">Visible in store</label>
        </div>
        <div class="d-grid gap-2">
          <button type="submit" class="btn lux-btn-gold"><?= $editCategory ? 'Update Category' : 'Create Category' ?></button>
          <?php if ($editCategory): ?>
            <a href="<?= url('admin/categories.php') ?>" class="btn btn-outline-secondary">Cancel Edit</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- List -->
  <div class="col-lg-8">
    <div class="panel-card">
      <div class="panel-head"><h2><i class="bi bi-tags me-2"></i>All Categories (<?= count($categories) ?>)</h2></div>
      <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
          <thead><tr><th>Category</th><th>Slug</th><th>Products</th><th>Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
            <?php foreach ($categories as $c): ?>
              <tr>
                <td>
                  <div class="product-cell">
                    <img src="<?= url('assets/images/products/' . e($c['image'])) ?>" alt="">
                    <strong><?= e($c['name']) ?></strong>
                  </div>
                </td>
                <td><code><?= e($c['slug']) ?></code></td>
                <td><span class="badge rounded-pill bg-light text-dark"><?= (int)$c['product_count'] ?></span></td>
                <td><?= (int)$c['sort_order'] ?></td>
                <td><?= $c['is_active'] ? '<span class="badge rounded-pill bg-success-subtle text-success">Visible</span>' : '<span class="badge rounded-pill bg-secondary-subtle text-secondary">Hidden</span>' ?></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="<?= url('admin/categories.php?edit=' . (int)$c['id']) ?>" class="btn btn-edit" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form method="post" action="<?= url('admin/categories.php') ?>" class="d-inline confirm-delete" data-name="<?= e($c['name']) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                      <button type="submit" class="btn btn-delete" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
