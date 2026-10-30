<?php
/**
 * VELOURA — Shop page with search, category filter, price range, sale filter, sorting & pagination.
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Shop';

$where  = ['p.is_active = 1'];
$params = [];

// --- Search ---
$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $where[] = '(p.name LIKE :q1 OR p.description LIKE :q2 OR p.short_description LIKE :q3)';
    $params['q1'] = '%' . $q . '%';
    $params['q2'] = '%' . $q . '%';
    $params['q3'] = '%' . $q . '%';
}

// --- Category filter ---
$activeCategory = null;
$catSlug = trim((string)($_GET['category'] ?? ''));
if ($catSlug !== '') {
    $activeCategory = get_category_by_slug($catSlug);
    if ($activeCategory) {
        $where[] = 'p.category_id = :cat';
        $params['cat'] = (int)$activeCategory['id'];
    }
}

// --- Price range ---
$minPrice = isset($_GET['min']) && $_GET['min'] !== '' ? (float)$_GET['min'] : null;
$maxPrice = isset($_GET['max']) && $_GET['max'] !== '' ? (float)$_GET['max'] : null;
if ($minPrice !== null && $minPrice >= 0) {
    $where[] = 'COALESCE(NULLIF(p.sale_price,0), p.price) >= :minp';
    $params['minp'] = $minPrice;
}
if ($maxPrice !== null && $maxPrice > 0) {
    $where[] = 'COALESCE(NULLIF(p.sale_price,0), p.price) <= :maxp';
    $params['maxp'] = $maxPrice;
}

// --- Sale filter ---
$saleOnly = isset($_GET['sale']) && $_GET['sale'] == '1';
if ($saleOnly) {
    $where[] = 'p.sale_price IS NOT NULL AND p.sale_price > 0';
}

// --- In-stock filter ---
$inStockOnly = isset($_GET['stock']) && $_GET['stock'] == '1';
if ($inStockOnly) {
    $where[] = 'p.stock > 0';
}

// --- Sorting ---
$sort  = (string)($_GET['sort'] ?? 'featured');
$sortMap = [
    'price_asc'  => 'COALESCE(NULLIF(p.sale_price,0), p.price) ASC',
    'price_desc' => 'COALESCE(NULLIF(p.sale_price,0), p.price) DESC',
    'newest'     => 'p.created_at DESC, p.id DESC',
    'name'       => 'p.name ASC',
    'featured'   => 'p.is_featured DESC, p.id ASC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['featured'];

$whereSql = implode(' AND ', $where);

// --- Count (pagination) ---
$countStmt = db()->prepare("SELECT COUNT(*) FROM products p WHERE $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();

$perPage = PRODUCTS_PER_PAGE;
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$offset = ($page - 1) * $perPage;

// --- Products ---
$sql = "SELECT p.*, c.name AS category_name FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE $whereSql ORDER BY $orderBy LIMIT :lim OFFSET :off";
$stmt = db()->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v);
}
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Wishlist ids
$inWishlistIds = [];
if (is_logged_in()) {
    $stmt = db()->prepare('SELECT product_id FROM wishlist WHERE user_id = ?');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $inWishlistIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

// Query string helper for pagination & filter links (keeps current filters)
function shop_query(array $overrides = []): string
{
    $defaults = [
        'q' => $GLOBALS['q'], 'category' => $GLOBALS['catSlug'],
        'min' => $_GET['min'] ?? '', 'max' => $_GET['max'] ?? '',
        'sale' => $GLOBALS['saleOnly'] ? '1' : '', 'stock' => $GLOBALS['inStockOnly'] ? '1' : '',
        'sort' => $GLOBALS['sort'],
    ];
    $merged = array_merge($defaults, $overrides);
    $merged = array_filter($merged, fn($v) => $v !== '' && $v !== null);
    return 'shop.php?' . http_build_query($merged);
}

include __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb" class="lux-breadcrumb">
  <div class="container">
    <h1 class="page-title"><?= $activeCategory ? e($activeCategory['name']) : ($q !== '' ? 'Search Results' : ($saleOnly ? 'Special Offers' : 'The Boutique')) ?></h1>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
      <li class="breadcrumb-item"><a href="<?= url('shop.php') ?>">Shop</a></li>
      <?php if ($activeCategory): ?><li class="breadcrumb-item active" aria-current="page"><?= e($activeCategory['name']) ?></li>
      <?php elseif ($q !== ''): ?><li class="breadcrumb-item active" aria-current="page">“<?= e($q) ?>”</li>
      <?php elseif ($saleOnly): ?><li class="breadcrumb-item active" aria-current="page">Sale</li>
      <?php else: ?><li class="breadcrumb-item active" aria-current="page">All Pieces</li><?php endif; ?>
    </ol>
  </div>
</nav>

<section class="section-pad pt-4">
  <div class="container">
    <div class="row g-4">
      <!-- ============ SIDEBAR FILTERS ============ -->
      <aside class="col-lg-3">
        <div class="shop-filters">
          <form method="get" action="<?= url('shop.php') ?>" id="filterForm">
            <?php if ($catSlug): ?><input type="hidden" name="category" value="<?= e($catSlug) ?>"><?php endif; ?>

            <div class="filter-box">
              <h6 class="filter-title"><i class="bi bi-search me-2"></i>Search</h6>
              <input type="search" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search pieces…">
            </div>

            <div class="filter-box">
              <h6 class="filter-title">Categories</h6>
              <ul class="filter-cats">
                <li><a href="<?= shop_query(['category' => '']) ?>" class="<?= $activeCategory ? '' : 'active' ?>">All Pieces <span>(<?= $totalRows ?>)</span></a></li>
                <?php foreach (get_categories() as $cat): ?>
                  <li><a href="<?= shop_query(['category' => $cat['slug']]) ?>" class="<?= $activeCategory && $activeCategory['id'] == $cat['id'] ? 'active' : '' ?>">
                    <?= e($cat['name']) ?> <span>(<?= (int)$cat['product_count'] ?>)</span></a></li>
                <?php endforeach; ?>
              </ul>
            </div>

            <div class="filter-box">
              <h6 class="filter-title">Price Range</h6>
              <div class="row g-2">
                <div class="col-6"><input type="number" class="form-control" name="min" min="0" step="1" placeholder="Min" value="<?= e($_GET['min'] ?? '') ?>"></div>
                <div class="col-6"><input type="number" class="form-control" name="max" min="0" step="1" placeholder="Max" value="<?= e($_GET['max'] ?? '') ?>"></div>
              </div>
            </div>

            <div class="filter-box">
              <h6 class="filter-title">Refine</h6>
              <div class="form-check lux-check">
                <input class="form-check-input" type="checkbox" name="sale" value="1" id="filterSale" <?= $saleOnly ? 'checked' : '' ?>>
                <label class="form-check-label" for="filterSale">On sale only</label>
              </div>
              <div class="form-check lux-check">
                <input class="form-check-input" type="checkbox" name="stock" value="1" id="filterStock" <?= $inStockOnly ? 'checked' : '' ?>>
                <label class="form-check-label" for="filterStock">In stock only</label>
              </div>
            </div>

            <button type="submit" class="btn lux-btn w-100">Apply Filters</button>
            <a href="<?= url('shop.php') ?>" class="btn lux-btn-ghost w-100 mt-2">Reset All</a>
          </form>
        </div>
      </aside>

      <!-- ============ PRODUCT GRID ============ -->
      <div class="col-lg-9">
        <div class="shop-toolbar">
          <p class="result-count">Showing <strong><?= count($products) ?></strong> of <strong><?= $totalRows ?></strong> pieces</p>
          <form method="get" action="<?= url('shop.php') ?>" class="sort-form" id="sortForm">
            <?php foreach (['q' => $q, 'category' => $catSlug, 'min' => $_GET['min'] ?? '', 'max' => $_GET['max'] ?? '', 'sale' => $saleOnly ? '1' : '', 'stock' => $inStockOnly ? '1' : ''] as $k => $v): if ($v !== ''): ?>
              <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
            <?php endif; endforeach; ?>
            <label class="me-2">Sort by</label>
            <select name="sort" id="sortSelect" class="form-select form-select-sm lux-select">
              <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>>Featured</option>
              <option value="newest"   <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
              <option value="price_asc"  <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
              <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
              <option value="name"     <?= $sort === 'name' ? 'selected' : '' ?>>Name A–Z</option>
            </select>
          </form>
        </div>

        <?php if (!$products): ?>
          <div class="empty-state text-center py-5">
            <i class="bi bi-search"></i>
            <h3>No pieces found</h3>
            <p class="text-muted">Try adjusting your search or filters — new treasures arrive every week.</p>
            <a href="<?= url('shop.php') ?>" class="btn lux-btn mt-2">Browse Everything</a>
          </div>
        <?php else: ?>
          <div class="row g-4">
            <?php foreach ($products as $i => $product): include __DIR__ . '/includes/product_card.php'; endforeach; ?>
          </div>

          <?php if ($totalPages > 1): ?>
            <nav class="lux-pagination mt-5" aria-label="Shop pages">
              <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="<?= pagination_link(shop_query(), $page - 1) ?>" aria-label="Previous"><i class="bi bi-chevron-left"></i></a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= pagination_link(shop_query(), $i) ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                  <a class="page-link" href="<?= pagination_link(shop_query(), $page + 1) ?>" aria-label="Next"><i class="bi bi-chevron-right"></i></a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
