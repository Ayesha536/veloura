<?php
/**
 * VELOURA — AJAX endpoint: cart operations (add / update / remove / count).
 * Returns JSON. CSRF token required (sent as header or POST field).
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// --- Identify AJAX via header or POST ---
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
    json_out(['success' => false, 'message' => 'Invalid or expired session. Please refresh the page.'], 419);
}

$action = (string)($_POST['action'] ?? '');
$productId = (int)($_POST['product_id'] ?? 0);

if ($action !== 'add' && $action !== 'update' && $action !== 'remove' && $action !== 'count') {
    json_out(['success' => false, 'message' => 'Unknown action.']);
}

if ($action !== 'count') {
    if ($productId <= 0) {
        json_out(['success' => false, 'message' => 'Invalid product.']);
    }
    $stmt = db()->prepare('SELECT id, name, stock FROM products WHERE id = ? AND is_active = 1');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) {
        json_out(['success' => false, 'message' => 'This piece is unavailable.']);
    }
}

switch ($action) {
    case 'add':
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $already = get_cart()[$productId] ?? 0;
        $newQty  = $already + $qty;
        if ($newQty > (int)$product['stock']) {
            $newQty = (int)$product['stock'];
            json_out([
                'success' => false,
                'message' => (int)$product['stock'] === 0
                    ? 'Sorry, "' . $product['name'] . '" is out of stock.'
                    : 'Only ' . (int)$product['stock'] . ' of "' . $product['name'] . '" in stock.',
                'cart_count' => cart_count(),
            ]);
        }
        add_to_cart($productId, $qty);
        json_out(['success' => true, 'message' => '"' . $product['name'] . '" added to your bag.', 'cart_count' => cart_count()]);

    case 'update':
        $qty = (int)($_POST['qty'] ?? 1);
        if ($qty > (int)$product['stock']) {
            $qty = (int)$product['stock'];
        }
        update_cart($productId, $qty);
        json_out(['success' => true, 'cart_count' => cart_count(), 'subtotal' => cart_subtotal(), 'shipping' => shipping_fee_for(cart_subtotal())]);

    case 'remove':
        remove_from_cart($productId);
        json_out(['success' => true, 'message' => 'Item removed.', 'cart_count' => cart_count(), 'subtotal' => cart_subtotal(), 'shipping' => shipping_fee_for(cart_subtotal())]);

    case 'count':
    default:
        json_out(['success' => true, 'cart_count' => cart_count()]);
}
