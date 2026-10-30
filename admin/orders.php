<?php
/**
 * VELOURA — Admin: orders list with status filter & search.
 */
require_once __DIR__ . '/../config/config.php';
require_admin();
$pageTitle = 'Orders';
$activeNav = 'orders';

$status = (string)($_GET['status'] ?? '');
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$where = [];
$params = [];
if (in_array($status, ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'], true)) {
    $where[] = 'o.status = :status';
    $params['status'] = $status;
}
if ($q !== '') {
    $where[] = '(o.order_number LIKE :q1 OR o.shipping_name LIKE :q2 OR o.shipping_email LIKE :q3)';
    $params['q1'] = "%$q%";
    $params['q2'] = "%$q%";
    $params['q3'] = "%$q%";
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM orders o $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT o.*, u.name AS customer_name,
        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
        FROM orders o JOIN users u ON u.id = o.user_id
        $whereSql ORDER BY o.created_at DESC LIMIT :lim OFFSET :off";
$stmt = db()->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue(':' . $k, $v);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="panel-card">
  <div class="panel-head d-flex flex-wrap justify-content-between gap-2">
    <h2><i class="bi bi-receipt me-2"></i>All Orders (<?= $totalRows ?>)</h2>
    <div class="btn-group btn-group-sm" role="group">
      <a href="<?= url('admin/orders.php') ?>" class="btn btn-filter <?= $status === '' ? 'active' : '' ?>">All</a>
      <?php foreach (['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $s): ?>
        <a href="<?= url('admin/orders.php?status=' . $s) ?>" class="btn btn-filter <?= $status === $s ? 'active' : '' ?>"><?= $s ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="panel-filters">
    <form method="get" action="<?= url('admin/orders.php') ?>" class="row g-2">
      <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
      <div class="col-md-6 col-lg-4">
        <input type="search" name="q" class="form-control" placeholder="Search order #, customer name or email…" value="<?= e($q) ?>">
      </div>
      <div class="col-auto"><button type="submit" class="btn lux-btn">Search</button></div>
      <?php if ($q): ?><div class="col-auto"><a href="<?= url('admin/orders.php' . ($status ? '?status=' . $status : '')) ?>" class="btn btn-outline-secondary">Reset</a></div><?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table admin-table align-middle">
      <thead><tr><th>Order #</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th class="text-end">Action</th></tr></thead>
      <tbody>
        <?php if (!$orders): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No orders found.</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><strong><?= e($o['order_number']) ?></strong></td>
            <td>
              <?= e($o['shipping_name']) ?>
              <small class="d-block text-muted"><?= e($o['shipping_email']) ?></small>
            </td>
            <td><?= (int)$o['item_count'] ?></td>
            <td><strong><?= money((float)$o['total']) ?></strong></td>
            <td>
              <small><?= e(ucfirst($o['payment_method'])) ?></small>
              <span class="badge rounded-pill <?= $o['payment_status'] === 'Paid' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' ?> d-block mt-1"><?= e($o['payment_status']) ?></span>
            </td>
            <td><?= order_status_badge($o['status']) ?></td>
            <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
            <td class="text-end"><a href="<?= url('admin/order_view.php?id=' . (int)$o['id']) ?>" class="btn btn-sm btn-view">Manage</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav class="p-3">
      <ul class="pagination justify-content-center mb-0">
        <?php for ($i = 1; $i <= $totalPages; $i++):
          $qs = http_build_query(array_filter(['status' => $status, 'q' => $q, 'page' => $i])); ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= e($qs) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
