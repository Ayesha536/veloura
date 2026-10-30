<?php
/**
 * VELOURA — Global header + navigation (storefront).
 */
$pageTitle = $pageTitle ?? SITE_NAME;
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e($pageTitle) ?> — <?= e(SITE_NAME) ?></title>
<meta name="description" content="Veloura — premium women's accessories: jewelry, handbags, watches & gift sets in lavender and silver.">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%236b5b7e'/%3E%3Ctext x='16' y='22' font-family='Georgia' font-size='17' fill='%23f3eef9' text-anchor='middle'%3EV%3C/text%3E%3C/svg%3E">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600&family=Jost:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=1.0">
</head>
<body class="<?= e($bodyClass) ?>" data-base="<?= e(base_url()) ?>/">

<!-- Announcement bar -->
<div class="announce-bar text-center">
  <i class="bi bi-gem me-2"></i><?= e(get_setting('announcement', 'Complimentary shipping on orders over ' . money(150) . ' — wrapped in our signature lavender box')) ?><i class="bi bi-gem ms-2"></i>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg lux-navbar sticky-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand" href="<?= url('index.php') ?>">
      <span class="brand-mark">V</span>
      <span class="brand-text">VELOURA</span>
      <span class="brand-sub d-none d-xl-inline">PARIS</span>
    </a>

    <button class="navbar-toggler lux-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="Toggle navigation">
      <i class="bi bi-list"></i>
    </button>

    <div class="collapse navbar-collapse d-none d-lg-flex" id="desktopNav">
      <ul class="navbar-nav mx-auto lux-nav">
        <li class="nav-item"><a class="nav-link" href="<?= url('index.php') ?>">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('shop.php') ?>">Shop</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('shop.php?sort=newest') ?>">New In</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('about.php') ?>">About</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('faq.php') ?>">FAQ</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('contact.php') ?>">Contact</a></li>
      </ul>

      <form class="d-flex lux-search" action="<?= url('shop.php') ?>" method="get" role="search">
        <div class="input-group">
          <input class="form-control" type="search" name="q" placeholder="Search jewelry, bags…" aria-label="Search" value="<?= e($_GET['q'] ?? '') ?>">
          <button class="btn" type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
        </div>
      </form>

      <ul class="navbar-nav flex-row align-items-center lux-icons ms-lg-3">
        <?php $u = current_user(); ?>
        <li class="nav-item dropdown">
          <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account">
            <i class="bi bi-person"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end lux-dropdown">
            <?php if ($u): ?>
              <li><span class="dropdown-item-text small text-muted">Signed in as<br><strong><?= e($u['name']) ?></strong></span></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= url('orders.php') ?>"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
              <li><a class="dropdown-item" href="<?= url('wishlist.php') ?>"><i class="bi bi-heart me-2"></i>Wishlist</a></li>
              <?php if ($u['role'] === 'admin'): ?>
                <li><a class="dropdown-item" href="<?= url('admin/index.php') ?>"><i class="bi bi-speedometer2 me-2"></i>Admin Panel</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= url('logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
            <?php else: ?>
              <li><a class="dropdown-item" href="<?= url('login.php') ?>"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</a></li>
              <li><a class="dropdown-item" href="<?= url('signup.php') ?>"><i class="bi bi-person-plus me-2"></i>Create Account</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link position-relative" href="<?= url('wishlist.php') ?>" aria-label="Wishlist">
            <i class="bi bi-heart"></i>
            <?php if (is_logged_in()): $wc = (int)(db_wishlist_count()); if ($wc > 0): ?>
              <span class="icon-badge"><?= $wc ?></span>
            <?php endif; endif; ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link position-relative" href="<?= url('cart.php') ?>" aria-label="Cart">
            <i class="bi bi-bag"></i>
            <?php $cc = cart_count(); if ($cc > 0): ?>
              <span class="icon-badge"><?= $cc ?></span>
            <?php endif; ?>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Mobile offcanvas nav -->
<div class="offcanvas offcanvas-end lux-offcanvas" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
  <div class="offcanvas-header">
    <span class="brand-text" id="mobileNavLabel">VELOURA</span>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form class="mb-4" action="<?= url('shop.php') ?>" method="get">
      <div class="input-group lux-search">
        <input class="form-control" type="search" name="q" placeholder="Search products…" value="<?= e($_GET['q'] ?? '') ?>">
        <button class="btn btn-dark" type="submit"><i class="bi bi-search"></i></button>
      </div>
    </form>
    <ul class="list-unstyled mobile-links">
      <li><a href="<?= url('index.php') ?>"><i class="bi bi-house-door me-2"></i>Home</a></li>
      <li><a href="<?= url('shop.php') ?>"><i class="bi bi-bag me-2"></i>Shop All</a></li>
      <li><a href="<?= url('shop.php?sort=newest') ?>"><i class="bi bi-stars me-2"></i>New In</a></li>
      <li><a href="<?= url('wishlist.php') ?>"><i class="bi bi-heart me-2"></i>Wishlist</a></li>
      <li><a href="<?= url('cart.php') ?>"><i class="bi bi-bag-check me-2"></i>Cart (<?= cart_count() ?>)</a></li>
      <li><a href="<?= url('about.php') ?>"><i class="bi bi-flower1 me-2"></i>About</a></li>
      <li><a href="<?= url('faq.php') ?>"><i class="bi bi-patch-question me-2"></i>FAQ</a></li>
      <li><a href="<?= url('contact.php') ?>"><i class="bi bi-envelope me-2"></i>Contact</a></li>
    </ul>
    <hr>
    <?php if (current_user()): ?>
      <a href="<?= url('logout.php') ?>" class="btn btn-outline-dark w-100">Sign Out</a>
    <?php else: ?>
      <div class="d-grid gap-2">
        <a href="<?= url('login.php') ?>" class="btn btn-dark">Sign In</a>
        <a href="<?= url('signup.php') ?>" class="btn btn-outline-dark">Create Account</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Flash messages -->
<div class="container flash-wrap">
  <?= render_flashes() ?>
</div>

<main>
