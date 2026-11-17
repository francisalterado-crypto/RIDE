<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');

if (!is_dir(STORAGE_PATH)) {
    @mkdir(STORAGE_PATH, 0755, true);
}

require BASE_PATH . '/app/helpers.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$config = require APP_PATH . '/config/config.php';
date_default_timezone_set((string) ($config['app']['timezone'] ?? 'Asia/Manila'));

// Scholarly records keep exactly what was typed: paper titles, DOIs and links are case-sensitive.
$skipTitleCase = preg_match('#^(admin/)?scholarly(/|$)#', request_path()) === 1;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $_POST !== [] && !$skipTitleCase) {
    normalize_request_post_text_fields($_POST);
}
unset($skipTitleCase);

try {
    \App\Core\Database::init($config['database']);
} catch (Throwable $e) {
    if (PHP_SAPI === 'cli') {
        throw $e;
    }
    // The error text can name the database host and user, so it goes to the server log, not to the visitor.
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>RIDE Hub unavailable</title>';
    echo '<style>body{font-family:Inter,system-ui,sans-serif;max-width:40rem;margin:4rem auto;padding:0 1.5rem;color:#1b2430;line-height:1.5}</style></head><body>';
    echo '<h1>RIDE Hub is temporarily unavailable</h1>';
    echo '<p>The application cannot reach its database. Please try again later. If the problem continues, contact the system administrator; the details are in the server error log.</p>';
    echo '</body></html>';
    exit;
}

if (PHP_SAPI !== 'cli') {
    $secure = (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_name('RIDESESSID');
    session_set_cookie_params([
        'lifetime' => (int) ($config['session']['lifetime'] ?? 7200),
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.gc_maxlifetime', (string) ($config['session']['lifetime'] ?? 7200));

    try {
        \App\Core\Database::ensureSessionsTable();
        session_set_save_handler(new \App\Core\PdoSessionHandler(\App\Core\Database::pdo()), true);
    } catch (Throwable) {
        ini_set('session.save_path', sys_get_temp_dir());
    }

    session_start();
}
