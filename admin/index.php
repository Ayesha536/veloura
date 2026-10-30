<?php
/**
 * VELOURA — Admin dashboard: statistics, revenue chart, recent orders, low stock.
 */
require_once __DIR__ . '/../config/config.php';
require_admin();
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

$stats = [
    'revenue'   => (float)db()->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'Cancelled'")->fetchColumn(),
    'orders'    => (int)db()->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'products'  => (int)db()->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'customers' => (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
];

$pendingOrders = (int)db()->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
$lowStock = db()->query('SELECT id, name, stock, image FROM products WHERE stock <= 5 AND is_active = 1 ORDER BY stock ASC LIMIT 6')->fetchAll();
$recentOrders = db()->query(
    "SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC LIMIT 6"
)->fetchAll();

// Revenue last 7 days (for Chart.js)
$chartLabels = [];
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('D j', strtotime($day));
    $stmt = db()->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at) = ? AND status != 'Cancelled'");
    $stmt->execute([$day]);
    $chartData[] = (float)$stmt->fetchColumn();
}

$statusCounts = [];
foreach (['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $s) {
    $stmt = db()->prepare('SELECT COUNT(*) FROM orders WHERE status = ?');
    $stmt->execute([$s]);
    $statusCounts[$s] = (int)$stmt->fetchColumn();
}

$loadChartJs = true;
include __DIR__ . '/../includes/admin_header.php';
?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
      <div><span class="stat-label">Total Revenue</span><span class="stat-value"><?= money($stats['revenue']) ?></span></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-receipt"></i></div>
      <div><span class="stat-label">Orders</span><span class="stat-value"><?= $stats['orders'] ?></span>
        <?php if ($pendingOrders > 0): ?><span class="stat-hint"><?= $pendingOrders ?> pending</span><?php endif; ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-gem"></i></div>
      <div><span class="stat-label">Products</span><span class="stat-value"><?= $stats['products'] ?></span></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-people"></i></div>
      <div><span class="stat-label">Customers</span><span class="stat-value"><?= $stats['customers'] ?></span></div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <!-- Revenue chart -->
  <div class="col-lg-8">
    <div class="panel-card">
      <div class="panel-head">
        <h2><i class="bi bi-graph-up me-2"></i>Revenue — Last 7 Days</h2>
      </div>
      <div class="panel-body">
        <canvas id="revenueChart" height="110"></canvas>
      </div>
    </div>
  </div>

  <!-- Order status -->
  <div class="col-lg-4">
    <div class="panel-card h-100">
      <div class="panel-head"><h2><i class="bi bi-pie-chart me-2"></i>Orders by Status</h2></div>
      <div class="panel-body">
        <?php foreach ($statusCounts as $s => $c): ?>
          <div class="status-row">
            <?= order_status_badge($s) ?>
            <div class="progress flex-grow-1 mx-3" style="height:6px">
              <div class="progress-bar status-bar-<?= strtolower($s) ?>" style="width: <?= $stats['orders'] > 0 ? $c / $stats['orders'] * 100 : 0 ?>%"></div>
            </div>
            <strong><?= $c ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Recent orders -->
  <div class="col-lg-8">
    <div class="panel-card">
      <div class="panel-head d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-clock-history me-2"></i>Recent Orders</h2>
        <a href="<?= url('admin/orders.php') ?>" class="lux-link small">View all</a>
      </div>
      <div class="table-responsive">
        <table class="table admin-table mb-0">
          <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
          <tbody>
            <?php if (!$recentOrders): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">No orders yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recentOrders as $o): ?>
              <tr>
                <td><strong><?= e($o['order_number']) ?></strong></td>
                <td><?= e($o['customer_name']) ?></td>
                <td><?= money((float)$o['total']) ?></td>
                <td><?= order_status_badge($o['status']) ?></td>
                <td><?= time_ago($o['created_at']) ?></td>
                <td><a href="<?= url('admin/order_view.php?id=' . (int)$o['id']) ?>" class="btn btn-sm btn-view">View</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Low stock -->
  <div class="col-lg-4">
    <div class="panel-card h-100">
      <div class="panel-head d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-exclamation-triangle me-2"></i>Low Stock</h2>
        <a href="<?= url('admin/products.php') ?>" class="lux-link small">Manage</a>
      </div>
      <div class="panel-body p-0">
        <?php if (!$lowStock): ?>
          <p class="text-center text-muted py-4 mb-0">All products are well stocked. ✨</p>
        <?php endif; ?>
        <?php foreach ($lowStock as $p): ?>
          <div class="low-stock-row">
            <img src="<?= url('assets/images/products/' . e($p['image'])) ?>" alt="">
            <div class="flex-grow-1">
              <p class="mb-0 fw-medium"><?= e($p['name']) ?></p>
              <small class="text-muted">SKU managed in Products</small>
            </div>
            <span class="badge rounded-pill <?= $p['stock'] == 0 ? 'bg-danger' : 'bg-warning text-dark' ?>"><?= (int)$p['stock'] ?> left</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const ctx = document.getElementById('revenueChart');
  if (ctx && typeof Chart !== 'undefined') {
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
          label: 'Revenue (<?= CURRENCY_SYMBOL ?>)',
          data: <?= json_encode($chartData) ?>,
          borderColor: '#967bb6',
          backgroundColor: 'rgba(150,123,182,0.15)',
          fill: true,
          tension: 0.4,
          borderWidth: 3,
          pointBackgroundColor: '#6b5b7e',
          pointRadius: 4
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, grid: { color: 'rgba(150,123,182,0.12)' }, ticks: { color: '#8a8496' } },
          x: { grid: { display: false }, ticks: { color: '#8a8496' } }
        }
      }
    });
  }
});
</script>
