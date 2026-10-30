<?php
/**
 * VELOURA — Admin: homepage banner CRUD with image upload.
 */
require_once __DIR__ . '/../config/config.php';
require_admin();
$pageTitle = 'Banners';
$activeNav = 'banners';

$editBanner = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $subtitle = trim((string)($_POST['subtitle'] ?? ''));
        $buttonText = trim((string)($_POST['button_text'] ?? ''));
        $link = trim((string)($_POST['link'] ?? 'shop.php'));
        $sort = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (mb_strlen($title) < 2) {
            set_flash('error', 'Banner title is required.');
            redirect(url('admin/banners.php'));
        }
        $newImage = upload_image($_FILES['image'] ?? null, ROOT_PATH . '/assets/images/banners', 'banner');

        if ($id > 0) {
            if ($newImage) {
                db()->prepare('UPDATE banners SET title=?, subtitle=?, image=?, button_text=?, link=?, sort_order=?, is_active=? WHERE id=?')
                    ->execute([$title, $subtitle, $newImage, $buttonText, $link, $sort, $isActive, $id]);
            } else {
                db()->prepare('UPDATE banners SET title=?, subtitle=?, button_text=?, link=?, sort_order=?, is_active=? WHERE id=?')
                    ->execute([$title, $subtitle, $buttonText, $link, $sort, $isActive, $id]);
            }
            set_flash('success', 'Banner updated.');
        } else {
            if (!$newImage) {
                set_flash('error', 'A banner image is required for new banners.');
                redirect(url('admin/banners.php'));
            }
            db()->prepare('INSERT INTO banners (title, subtitle, image, button_text, link, sort_order, is_active) VALUES (?,?,?,?,?,?,?)')
                ->execute([$title, $subtitle, $newImage, $buttonText, $link, $sort, $isActive]);
            set_flash('success', 'Banner created.');
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM banners WHERE id = ?')->execute([$id]);
        set_flash('success', 'Banner deleted.');
    }

    redirect(url('admin/banners.php'));
}

if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM banners WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editBanner = $stmt->fetch() ?: null;
}

$banners = db()->query('SELECT * FROM banners ORDER BY sort_order ASC, id ASC')->fetchAll();
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel-card">
      <div class="panel-head">
        <h2><i class="bi bi-<?= $editBanner ? 'pencil' : 'plus-lg' ?> me-2"></i><?= $editBanner ? 'Edit Banner' : 'Add Banner' ?></h2>
      </div>
      <form method="post" action="<?= url('admin/banners.php') ?>" enctype="multipart/form-data" class="panel-body">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editBanner['id'] ?? 0) ?>">
        <div class="mb-3">
          <label class="form-label lux-label" for="bnTitle">Title *</label>
          <input type="text" id="bnTitle" name="title" class="form-control" value="<?= e($editBanner['title'] ?? '') ?>" required maxlength="120">
        </div>
        <div class="mb-3">
          <label class="form-label lux-label" for="bnSubtitle">Subtitle / Eyebrow</label>
          <input type="text" id="bnSubtitle" name="subtitle" class="form-control" value="<?= e($editBanner['subtitle'] ?? '') ?>" maxlength="120">
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label lux-label" for="bnBtn">Button Text</label>
            <input type="text" id="bnBtn" name="button_text" class="form-control" value="<?= e($editBanner['button_text'] ?? '') ?>" maxlength="40">
          </div>
          <div class="col-6">
            <label class="form-label lux-label" for="bnLink">Button Link</label>
            <input type="text" id="bnLink" name="link" class="form-control" value="<?= e($editBanner['link'] ?? 'shop.php') ?>" placeholder="shop.php">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label lux-label" for="bnImage"><?= $editBanner ? 'Replace Image (optional)' : 'Image *' ?></label>
          <input type="file" id="bnImage" name="image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" <?= $editBanner ? '' : 'required' ?>>
          <div class="form-text">Wide images (1920×900) look best.</div>
        </div>
        <div class="mb-3">
          <label class="form-label lux-label" for="bnSort">Sort Order</label>
          <input type="number" id="bnSort" name="sort_order" class="form-control" value="<?= (int)($editBanner['sort_order'] ?? 0) ?>">
        </div>
        <div class="form-check lux-switch mb-3">
          <input class="form-check-input" type="checkbox" id="bnActive" name="is_active" value="1" <?= ($editBanner === null || (int)$editBanner['is_active'] === 1) ? 'checked' : '' ?>>
          <label class="form-check-label" for="bnActive">Show on homepage</label>
        </div>
        <div class="d-grid gap-2">
          <button type="submit" class="btn lux-btn-gold"><?= $editBanner ? 'Update Banner' : 'Create Banner' ?></button>
          <?php if ($editBanner): ?><a href="<?= url('admin/banners.php') ?>" class="btn btn-outline-secondary">Cancel Edit</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="panel-card">
      <div class="panel-head"><h2><i class="bi bi-images me-2"></i>Homepage Banners (<?= count($banners) ?>)</h2></div>
      <div class="row g-3 p-3">
        <?php foreach ($banners as $b): ?>
          <div class="col-md-6">
            <div class="banner-tile <?= $b['is_active'] ? '' : 'inactive' ?>">
              <img src="<?= url('assets/images/banners/' . e($b['image'])) ?>" alt="">
              <div class="banner-tile-body">
                <strong><?= e($b['title']) ?></strong>
                <small class="text-muted d-block mb-2"><?= e($b['subtitle']) ?></small>
                <span class="badge rounded-pill <?= $b['is_active'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                  <?= $b['is_active'] ? 'Live' : 'Hidden' ?>
                </span>
                <span class="badge rounded-pill bg-light text-dark">Order <?= (int)$b['sort_order'] ?></span>
                <div class="mt-2 d-flex gap-2">
                  <a href="<?= url('admin/banners.php?edit=' . (int)$b['id']) ?>" class="btn btn-sm btn-edit"><i class="bi bi-pencil me-1"></i>Edit</a>
                  <form method="post" action="<?= url('admin/banners.php') ?>" class="d-inline confirm-delete" data-name="banner: <?= e($b['title']) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-delete"><i class="bi bi-trash me-1"></i>Delete</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$banners): ?>
          <p class="text-center text-muted py-4 mb-0">No banners yet — create the first one.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
