<?php
/**
 * VELOURA — AJAX endpoint: wishlist toggle (requires login).
 * Returns JSON.
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if (!is_logged_in()) {
    json_out(['success' => false, 'login_required' => true, 'message' => 'Please sign in to save pieces to your wishlist.'], 401);
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
    json_out(['success' => false, 'message' => 'Invalid or expired session. Please refresh the page.'], 419);
}

$productId = (int)($_POST['product_id'] ?? 0);
if ($productId <= 0) {
    json_out(['success' => false, 'message' => 'Invalid product.']);
}

$stmt = db()->prepare('SELECT id, name FROM products WHERE id = ? AND is_active = 1');
$stmt->execute([$productId]);
if (!$stmt->fetch()) {
    json_out(['success' => false, 'message' => 'Product not found.']);
}

$userId = (int)$_SESSION['user_id'];
$stmt = db()->prepare('SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?');
$stmt->execute([$userId, $productId]);
$existing = $stmt->fetch();

if ($existing) {
    db()->prepare('DELETE FROM wishlist WHERE id = ?')->execute([$existing['id']]);
    $inWishlist = false;
    $message = 'Removed from your wishlist.';
} else {
    db()->prepare('INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())')->execute([$userId, $productId]);
    $inWishlist = true;
    $message = 'Saved to your wishlist.';
}

$stmt = db()->prepare('SELECT COUNT(*) AS c FROM wishlist WHERE user_id = ?');
$stmt->execute([$userId]);
$count = (int)$stmt->fetch()['c'];

json_out(['success' => true, 'in_wishlist' => $inWishlist, 'message' => $message, 'wishlist_count' => $count]);
