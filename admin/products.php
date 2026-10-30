<?php
/**
 * VELOURA — Admin: products list with search, filter & delete.
 */
require_once __DIR__ . '/../config/config.php';
require_admin();
$pageTitle = 'Products';
$activeNav = 'products';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare('SELECT image FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if ($product) {
        db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        // remove main image file (gallery leftovers are harmless)
        if (!empty($product['image']) && is_file(ROOT_PATH . '/assets/images/products/' . $product['image'])) {
            @unlink(ROOT_PATH . '/assets/images/products/' . $product['image']);
        }
        set_flash('success', 'Product deleted successfully.');
    } else {
        set_flash('error', 'Product not found.');
    }
    redirect(url('admin/products.php'));
}

// Filters
$q = trim((string)($_GET['q'] ?? ''));
$catId = (int)($_GET['category'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE :q1 OR p.sku LIKE :q2)';
    $params['q1'] = "%$q%";
    $params['q2'] = "%$q%";
}
if ($catId > 0) {
    $where[] = 'p.category_id = :cat';
    $params['cat'] = $catId;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM products p $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT p.*, c.name AS category_name FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        $whereSql ORDER BY p.id DESC LIMIT :lim OFFSET :off";
$stmt = db()->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue(':' . $k, $v);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="panel-card">
  <div class="panel-head d-flex flex-wrap justify-content-between gap-2 align-items-center">
    <h2><i class="bi bi-gem me-2"></i>All Products (<?= $totalRows ?>)</h2>
    <a href="<?= url('admin/product_form.php') ?>" class="btn lux-btn-gold"><i class="bi bi-plus-lg me-1"></i> Add Product</a>
  </div>

  <div class="panel-filters">
    <form method="get" action="<?= url('admin/products.php') ?>" class="row g-2 align-items-center">
      <div class="col-md-5">
        <input type="search" name="q" class="form-control" placeholder="Search by name or SKU…" value="<?= e($q) ?>">
      </div>
      <div class="col-md-4">
        <select name="category" class="form-select">
          <option value="0">All categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $catId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn lux-btn flex-grow-1">Filter</button>
        <a href="<?= url('admin/products.php') ?>" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table admin-table align-middle">
      <thead>
        <tr>
          <th>Product</th><th>Category</th><th>Price</th><th>Stock</th>
          <th>Featured</th><th>Status</th><th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$products): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No products found.</td></tr>
        <?php endif; ?>
        <?php foreach ($products as $p): $sale = (float)($p['sale_price'] ?: 0); ?>
          <tr>
            <td>
              <div class="product-cell">
                <img src="<?= url('assets/images/products/' . e($p['image'])) ?>" alt="">
                <div>
                  <strong><?= e($p['name']) ?></strong>
                  <small class="d-block text-muted">SKU: <?= e($p['sku'] ?: '—') ?></small>
                </div>
              </div>
            </td>
            <td><?= e($p['category_name'] ?? '—') ?></td>
            <td>
              <?php if ($sale > 0): ?>
                <strong class="text-success"><?= money($sale) ?></strong>
                <small class="d-block text-muted text-decoration-line-through"><?= money((float)$p['price']) ?></small>
              <?php else: ?>
                <strong><?= money((float)$p['price']) ?></strong>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge rounded-pill <?= (int)$p['stock'] === 0 ? 'bg-danger' : ((int)$p['stock'] <= 5 ? 'bg-warning text-dark' : 'bg-success-subtle text-success') ?>">
                <?= (int)$p['stock'] ?>
              </span>
            </td>
            <td><?= $p['is_featured'] ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-muted"></i>' ?></td>
            <td><?= $p['is_active'] ? '<span class="badge rounded-pill bg-success-subtle text-success">Active</span>' : '<span class="badge rounded-pill bg-secondary-subtle text-secondary">Hidden</span>' ?></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>" class="btn btn-view" target="_blank" title="View in store"><i class="bi bi-eye"></i></a>
                <a href="<?= url('admin/product_form.php?id=' . (int)$p['id']) ?>" class="btn btn-edit" title="Edit"><i class="bi bi-pencil"></i></a>
                <form method="post" action="<?= url('admin/products.php') ?>" class="d-inline confirm-delete" data-name="<?= e($p['name']) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button type="submit" class="btn btn-delete" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav class="p-3">
      <ul class="pagination justify-content-center mb-0">
        <?php for ($i = 1; $i <= $totalPages; $i++):
          $qs = http_build_query(array_filter(['q' => $q, 'category' => $catId ?: '', 'page' => $i])); ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= e($qs) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
