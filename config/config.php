<?php
/**
 * VELOURA — Premium Women's Accessories
 * Central configuration file.
 *
 * Edit the database credentials below to match your XAMPP setup.
 * Default XAMPP MySQL: host=localhost, user=root, empty password.
 */

// ---------- Database ----------
// Optional local override file (config.local.php) may pre-define $VELA_DB.
// Useful for staging/demo servers; safe to delete on a standard XAMPP install.
$dbHost = 'localhost'; $dbName = 'veloura'; $dbUser = 'root'; $dbPass = ''; $dbCharset = 'utf8mb4';
if (is_file(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
define('DB_HOST', $dbHost);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_CHARSET', $dbCharset);

// ---------- Site ----------
define('SITE_NAME', 'Veloura');
define('SITE_TAGLINE', 'Timeless Elegance, Thoughtfully Curated');
define('CURRENCY_SYMBOL', '$');
define('PRODUCTS_PER_PAGE', 12);

// ---------- Paths ----------
define('ROOT_PATH', dirname(__DIR__));

// ---------- Session hardening ----------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // Enable 'secure' => true when serving over HTTPS
    ]);
}
session_start();

// Default timezone
date_default_timezone_set('UTC');

// Error reporting — set to 0 in production
define('APP_DEBUG', true);
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/auth.php';
