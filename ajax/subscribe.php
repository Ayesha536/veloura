<?php
/**
 * VELOURA — AJAX endpoint: newsletter subscribe.
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid session. Please refresh the page.']);
    exit;
}

$email = strtolower(trim((string)($_POST['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$stmt = db()->prepare('SELECT id FROM subscribers WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => true, 'message' => 'You are already on the list — welcome back!']);
    exit;
}

db()->prepare('INSERT INTO subscribers (email, created_at) VALUES (?, NOW())')->execute([$email]);
echo json_encode(['success' => true, 'message' => 'Welcome to the Veloura list — your first offer is on its way.']);
