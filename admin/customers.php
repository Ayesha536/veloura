<?php
/**
 * VELOURA — Admin: customers list with order stats, block/unblock.
 */
require_once __DIR__ . '/../config/config.php';
require_admin();
$pageTitle = 'Customers';
$activeNav = 'customers';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare("SELECT id, name, status FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$id]);
    if ($user = $stmt->fetch()) {
        $newStatus = $user['status'] === 'active' ? 'blocked' : 'active';
        db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
        set_flash('success', $newStatus === 'blocked'
            ? $user['name'] . ' has been blocked and can no longer sign in.'
            : $user['name'] . ' has been unblocked.');
    }
    redirect(url('admin/customers.php'));
}

$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$where = ["u.role = 'customer'"];
$params = [];
if ($q !== '') {
    $where[] = '(u.name LIKE :q1 OR u.email LIKE :q2 OR u.phone LIKE :q3)';
    $params['q1'] = "%$q%";
    $params['q2'] = "%$q%";
    $params['q3'] = "%$q%";
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM users u $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT u.*,
        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
        (SELECT COALESCE(SUM(o.total),0) FROM orders o WHERE o.user_id = u.id AND o.status != 'Cancelled') AS total_spent
        FROM users u $whereSql ORDER BY u.created_at DESC LIMIT :lim OFFSET :off";
$stmt = db()->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue(':' . $k, $v);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll();

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="panel-card">
  <div class="panel-head d-flex flex-wrap justify-content-between gap-2">
    <h2><i class="bi bi-people me-2"></i>Customers (<?= $totalRows ?>)</h2>
    <form method="get" action="<?= url('admin/customers.php') ?>" class="d-flex gap-2">
      <input type="search" name="q" class="form-control" placeholder="Search name, email, phone…" value="<?= e($q) ?>" style="min-width:260px">
      <button type="submit" class="btn lux-btn">Search</button>
      <?php if ($q): ?><a href="<?= url('admin/customers.php') ?>" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table admin-table align-middle">
      <thead><tr><th>Customer</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Joined</th><th>Status</th><th class="text-end">Action</th></tr></thead>
      <tbody>
        <?php if (!$customers): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No customers found.</td></tr>
        <?php endif; ?>
        <?php foreach ($customers as $cust): ?>
          <tr>
            <td>
              <div class="product-cell">
                <span class="avatar-circle sm"><?= e(strtoupper(substr($cust['name'], 0, 1))) ?></span>
                <div>
                  <strong><?= e($cust['name']) ?></strong>
                  <small class="d-block text-muted"><?= e($cust['email']) ?></small>
                </div>
              </div>
            </td>
            <td><?= e($cust['phone'] ?: '—') ?></td>
            <td><span class="badge rounded-pill bg-light text-dark"><?= (int)$cust['order_count'] ?></span></td>
            <td><strong><?= money((float)$cust['total_spent']) ?></strong></td>
            <td><?= date('M j, Y', strtotime($cust['created_at'])) ?></td>
            <td>
              <?= $cust['status'] === 'active'
                ? '<span class="badge rounded-pill bg-success-subtle text-success">Active</span>'
                : '<span class="badge rounded-pill bg-danger-subtle text-danger">Blocked</span>' ?>
            </td>
            <td class="text-end">
              <form method="post" action="<?= url('admin/customers.php') ?>" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?= (int)$cust['id'] ?>">
                <button type="submit" class="btn btn-sm <?= $cust['status'] === 'active' ? 'btn-delete' : 'btn-view' ?>">
                  <?= $cust['status'] === 'active' ? '<i class="bi bi-lock"></i> Block' : '<i class="bi bi-unlock"></i> Unblock' ?>
                </button>
              </form>
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
          $qs = http_build_query(array_filter(['q' => $q, 'page' => $i])); ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= e($qs) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
