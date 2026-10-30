<?php
/**
 * VELOURA — Shared helper functions.
 */

/* ---------------- Output escaping ---------------- */

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* ---------------- URL building ---------------- */

/** Base URL of the project (auto-detects /veloura or sub-folder, works in /admin and /ajax too). */
function base_url(): string
{
    static $base = null;
    if ($base === null) {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        if (preg_match('~/(admin|ajax)$~', $dir)) {
            $dir = substr($dir, 0, strrpos($dir, '/'));
        }
        $base = rtrim($dir, '/');
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/* ---------------- CSRF protection ---------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        die('Invalid CSRF token. Please go back, refresh the page and try again.');
    }
}

/* ---------------- Flash messages ---------------- */

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function render_flashes(): string
{
    $map = ['success' => 'success', 'error' => 'danger', 'info' => 'info', 'warning' => 'warning'];
    $html = '';
    foreach (get_flashes() as $f) {
        $type = $map[$f['type']] ?? 'info';
        $html .= '<div class="alert alert-' . $type . ' alert-dismissible fade show lux-alert" role="alert">'
               . e($f['message'])
               . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    return $html;
}

/* ---------------- Formatting ---------------- */

function money(float|int|null $n): string
{
    return CURRENCY_SYMBOL . number_format((float)$n, 2);
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item';
}

function time_ago(string $datetime): string
{
    $ts = strtotime($datetime);
    if (!$ts) return $datetime;
    $diff = time() - $ts;
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . ' min ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day ago';
    return date('M j, Y', $ts);
}

/* ---------------- Settings (site_content) ---------------- */

function get_setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
            foreach ($rows as $r) {
                $cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Throwable $e) {
            // table may not exist yet
        }
    }
    return $cache[$key] ?? $default;
}

function save_setting(string $key, string $value): void
{
    $stmt = db()->prepare('SELECT id FROM settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    if ($stmt->fetch()) {
        db()->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?')->execute([$value, $key]);
    } else {
        db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)')->execute([$key, $value]);
    }
}

/* ---------------- Categories ---------------- */

function get_categories(bool $activeOnly = true): array
{
    $sql = 'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) AS product_count
            FROM categories c';
    if ($activeOnly) {
        $sql .= ' WHERE c.is_active = 1';
    }
    $sql .= ' ORDER BY c.sort_order ASC, c.name ASC';
    return db()->query($sql)->fetchAll();
}

function get_category_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE slug = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/* ---------------- Shopping cart (session based) ---------------- */

function &get_cart(): array
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function cart_count(): int
{
    $count = 0;
    foreach (get_cart() as $qty) {
        $count += (int)$qty;
    }
    return $count;
}

/** Returns cart rows joined with fresh product data (price/stock are never trusted from session). */
function cart_items(): array
{
    $cart = get_cart();
    if (!$cart) return [];

    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND is_active = 1");
    $stmt->execute($ids);
    $products = [];
    foreach ($stmt->fetchAll() as $p) {
        $products[(int)$p['id']] = $p;
    }

    $items = [];
    foreach ($cart as $pid => $qty) {
        $pid = (int)$pid;
        if (!isset($products[$pid])) continue; // product removed/disabled
        $p = $products[$pid];
        $qty = max(1, (int)$qty);
        $price = $p['sale_price'] !== null && $p['sale_price'] !== '' ? (float)$p['sale_price'] : (float)$p['price'];
        $items[] = [
            'product'    => $p,
            'qty'        => $qty,
            'price'      => $price,
            'line_total' => $price * $qty,
            'over_stock' => $qty > (int)$p['stock'],
        ];
    }
    return $items;
}

function cart_subtotal(): float
{
    $sum = 0.0;
    foreach (cart_items() as $item) {
        $sum += $item['line_total'];
    }
    return $sum;
}

function shipping_fee_for(float $subtotal): float
{
    $threshold = (float)get_setting('free_shipping_threshold', '150');
    $fee = (float)get_setting('shipping_fee', '12');
    if ($subtotal <= 0) return 0.0;
    return $subtotal >= $threshold ? 0.0 : $fee;
}

function add_to_cart(int $productId, int $qty = 1): void
{
    $cart = &get_cart();
    $cart[$productId] = ($cart[$productId] ?? 0) + max(1, $qty);
}

function update_cart(int $productId, int $qty): void
{
    $cart = &get_cart();
    if ($qty <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = $qty;
    }
}

function remove_from_cart(int $productId): void
{
    $cart = &get_cart();
    unset($cart[$productId]);
}

function clear_cart(): void
{
    $_SESSION['cart'] = [];
}

/* ---------------- Wishlist (database) ---------------- */

function db_wishlist_count(): int
{
    if (!is_logged_in()) return 0;
    $stmt = db()->prepare('SELECT COUNT(*) AS c FROM wishlist WHERE user_id = ?');
    $stmt->execute([(int)$_SESSION['user_id']]);
    return (int)($stmt->fetch()['c'] ?? 0);
}

/* ---------------- Orders ---------------- */

function generate_order_number(): string
{
    return 'VLR-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function order_status_badge(string $status): string
{
    $map = [
        'Pending'    => 'warning',
        'Processing' => 'info',
        'Shipped'    => 'primary',
        'Delivered'  => 'success',
        'Cancelled'  => 'danger',
    ];
    $cls = $map[$status] ?? 'secondary';
    return '<span class="badge rounded-pill status-badge status-' . strtolower($status) . '">' . e($status) . '</span>';
}

/* ---------------- Image uploads (admin) ---------------- */

/**
 * Validates and moves an uploaded image. Returns the stored filename or null on failure.
 */
function upload_image(?array $file, string $destDir, string $prefix = 'img'): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    if (($file['size'] ?? 0) > 4 * 1024 * 1024) {
        set_flash('error', 'Image is too large (max 4 MB).');
        return null;
    }
    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if ($info === false || !isset($allowed[$info[2]])) {
        set_flash('error', 'Invalid image format. Allowed: JPG, PNG, WEBP, GIF.');
        return null;
    }
    if (!is_dir($destDir)) {
        mkdir($destDir, 0775, true);
    }
    $ext  = $allowed[$info[2]];
    $name = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $name)) {
        set_flash('error', 'Could not save the uploaded image.');
        return null;
    }
    return $name;
}

/* ---------------- Misc ---------------- */

function pagination_link(string $baseQuery, int $page): string
{
    $sep = strpos($baseQuery, '?') !== false ? '&' : '?';
    return e($baseQuery . $sep . 'page=' . $page);
}

function stars_html(float $rating): string
{
    $full = (int)floor($rating);
    $half = ($rating - $full) >= 0.5;
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full) {
            $html .= '<i class="bi bi-star-fill"></i>';
        } elseif ($i === $full + 1 && $half) {
            $html .= '<i class="bi bi-star-half"></i>';
        } else {
            $html .= '<i class="bi bi-star"></i>';
        }
    }
    return '<span class="stars">' . $html . '</span>';
}
