<?php
/**
 * VELOURA — PDO database connection (singleton).
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo '<!DOCTYPE html><html><head><meta charset="utf-8">'
               . '<meta name="viewport" content="width=device-width, initial-scale=1">'
               . '<title>Database Error — Veloura</title>'
               . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
               . '</head><body class="bg-light">'
               . '<div class="container py-5" style="max-width:640px">'
               . '<div class="card shadow-sm border-0"><div class="card-body p-5 text-center">'
               . '<h1 class="h3 fw-bold mb-3" style="color:#6b5b7e">Veloura</h1>'
               . '<h2 class="h5 mb-3">Database connection failed</h2>'
               . '<p class="text-muted mb-4">We could not connect to the MySQL database. '
               . 'Please make sure MySQL is running in your XAMPP control panel and that the '
               . '<code>veloura</code> database has been imported (see <code>database/veloura.sql</code> '
               . 'and <code>README.md</code> for setup steps).</p>'
               . '<a class="btn btn-dark" href="javascript:location.reload()">Retry</a></div>'
               . '</div></div></div></body></html>';
            exit;
        }
    }
    return $pdo;
}
