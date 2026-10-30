<?php
/**
 * VELOURA — Authentication, session & role management.
 */

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $stmt = db()->prepare("SELECT id, name, email, phone, role, status FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$_SESSION['user_id']]);
            $row = $stmt->fetch();
            if ($row && $row['status'] === 'active') {
                $user = $row;
            } else {
                unset($_SESSION['user_id']); // deleted or blocked account
            }
        }
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function login_user(array $user): void
{
    session_regenerate_id(true); // prevent session fixation
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
}

function logout_user(): void
{
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
}

/* ------------ Admin (separate session key + role check) ------------ */

function current_admin(): ?array
{
    static $admin = false;
    if ($admin === false) {
        $admin = null;
        if (!empty($_SESSION['admin_id'])) {
            $stmt = db()->prepare("SELECT id, name, email, role FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
            $stmt->execute([(int)$_SESSION['admin_id']]);
            $row = $stmt->fetch();
            if ($row) {
                $admin = $row;
            } else {
                unset($_SESSION['admin_id']);
            }
        }
    }
    return $admin;
}

function login_admin(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id']   = (int)$user['id'];
    $_SESSION['admin_name'] = $user['name'];
}

function logout_admin(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_name']);
}

/** Guard for customer-only pages. Stores intended URL for post-login redirect. */
function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? url('shop.php');
        set_flash('info', 'Please sign in to continue.');
        redirect(url('login.php'));
    }
}

/** Guard for admin pages — role-based authorization. */
function require_admin(): void
{
    if (current_admin() === null) {
        set_flash('error', 'Please sign in with an administrator account.');
        redirect(url('admin/login.php'));
    }
}

/* ------------ Password helpers ------------ */

function hash_password(string $plain): string
{
    return password_hash($plain, PASSWORD_DEFAULT);
}

function verify_password(string $plain, string $hash): bool
{
    return password_verify($plain, $hash);
}

/** Server-side password policy: min 8 chars, at least one letter and one number. */
function valid_password(string $password): bool
{
    return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/\d/', $password);
}

/** Simple rate limiting for login attempts (per session). */
function throttle_check(string $key): bool
{
    $now = time();
    $attempts = $_SESSION['login_attempts'][$key] ?? ['count' => 0, 'until' => 0];
    if ($attempts['until'] > $now) {
        return false; // still locked
    }
    return true;
}

function throttle_fail(string $key): void
{
    $attempts = $_SESSION['login_attempts'][$key] ?? ['count' => 0, 'until' => 0];
    $attempts['count']++;
    if ($attempts['count'] >= 5) {
        $attempts['count'] = 0;
        $attempts['until'] = time() + 300; // lock 5 minutes
    }
    $_SESSION['login_attempts'][$key] = $attempts;
}

function throttle_clear(string $key): void
{
    unset($_SESSION['login_attempts'][$key]);
}
