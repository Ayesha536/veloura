<?php
/**
 * VELOURA — Admin panel header.
 * Requires an authenticated admin (require_admin must have been called).
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> — Veloura Admin</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%23372a45'/%3E%3Ctext x='16' y='22' font-family='Georgia' font-size='17' fill='%23e9d9f7' text-anchor='middle'%3EV%3C/text%3E%3C/svg%3E">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>?v=1.0">
</head>
<body class="admin-body">

<div class="admin-wrapper">
  <!-- Sidebar -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
      <span class="brand-mark">V</span>
      <div>
        <span class="brand-text">VELOURA</span>
        <span class="brand-role">Admin Suite</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <p class="sidebar-label">Overview</p>
      <a href="<?= url('admin/index.php') ?>" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>

      <p class="sidebar-label">Catalog</p>
      <a href="<?= url('admin/products.php') ?>" class="<?= in_array($activeNav, ['products', 'product_form']) ? 'active' : '' ?>"><i class="bi bi-gem"></i> Products</a>
      <a href="<?= url('admin/categories.php') ?>" class="<?= $activeNav === 'categories' ? 'active' : '' ?>"><i class="bi bi-tags"></i> Categories</a>
      <a href="<?= url('admin/banners.php') ?>" class="<?= $activeNav === 'banners' ? 'active' : '' ?>"><i class="bi bi-images"></i> Banners</a>

      <p class="sidebar-label">Sales</p>
      <a href="<?= url('admin/orders.php') ?>" class="<?= in_array($activeNav, ['orders', 'order_view']) ? 'active' : '' ?>"><i class="bi bi-receipt"></i> Orders</a>
      <a href="<?= url('admin/customers.php') ?>" class="<?= $activeNav === 'customers' ? 'active' : '' ?>"><i class="bi bi-people"></i> Customers</a>

      <p class="sidebar-label">Website</p>
      <a href="<?= url('admin/faqs.php') ?>" class="<?= $activeNav === 'faqs' ? 'active' : '' ?>"><i class="bi bi-patch-question"></i> FAQs</a>
      <a href="<?= url('admin/content.php') ?>" class="<?= $activeNav === 'content' ? 'active' : '' ?>"><i class="bi bi-sliders"></i> Site Content</a>
      <a href="<?= url('admin/messages.php') ?>" class="<?= $activeNav === 'messages' ? 'active' : '' ?>">
        <i class="bi bi-envelope"></i> Messages
        <?php
        try {
            $unread = (int)db()->query("SELECT COUNT(*) c FROM contact_messages WHERE is_read = 0")->fetch()['c'];
            if ($unread > 0) echo '<span class="badge rounded-pill sidebar-pill">' . $unread . '</span>';
        } catch (Throwable $e) {}
        ?>
      </a>

      <p class="sidebar-label">Storefront</p>
      <a href="<?= url('index.php') ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> View Website</a>
      <a href="<?= url('admin/logout.php') ?>"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
    </nav>

    <div class="sidebar-footer">
      <i class="bi bi-shield-lock"></i> Secured admin area
    </div>
  </aside>

  <!-- Main -->
  <div class="admin-main">
    <header class="admin-topbar">
      <button class="btn sidebar-toggle d-lg-none" id="sidebarToggle" aria-label="Toggle sidebar"><i class="bi bi-list"></i></button>
      <h1 class="topbar-title"><?= e($pageTitle) ?></h1>
      <div class="topbar-right">
        <span class="topbar-date d-none d-md-inline"><i class="bi bi-calendar3 me-1"></i><?= date('D, M j, Y') ?></span>
        <div class="dropdown">
          <button class="admin-avatar" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="avatar-circle"><?= e(strtoupper(substr(current_admin()['name'] ?? 'A', 0, 1))) ?></span>
            <span class="avatar-name d-none d-sm-inline"><?= e(current_admin()['name'] ?? 'Admin') ?></span>
            <i class="bi bi-chevron-down small d-none d-sm-inline"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= url('admin/index.php') ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= url('admin/logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
          </ul>
        </div>
      </div>
    </header>

    <div class="admin-content">
      <?= render_flashes() ?>
