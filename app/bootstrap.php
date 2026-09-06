<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define(
    'STORAGE_PATH',
    (getenv('VERCEL') ? rtrim((string) sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'ride-storage' : BASE_PATH . '/storage')
);

if (!is_dir(STORAGE_PATH)) {
    @mkdir(STORAGE_PATH, 0755, true);
}

require BASE_PATH . '/app/helpers.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $_POST !== []) {
    normalize_request_post_text_fields($_POST);
}

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

try {
    if (is_vercel() && !database_configured($config['database'])) {
        throw new RuntimeException(
            'Database is not configured. In the Vercel project, set DATABASE_URL (Neon Postgres) or DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS.'
        );
    }
    \App\Core\Database::init($config['database']);
} catch (Throwable $e) {
    if (PHP_SAPI === 'cli') {
        throw $e;
    }
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>RIDE setup</title>';
    echo '<style>body{font-family:Inter,system-ui,sans-serif;max-width:40rem;margin:4rem auto;padding:0 1.5rem;color:#1b2430;line-height:1.5}code{background:#f3f5f8;padding:.1rem .35rem;border-radius:4px}</style></head><body>';
    echo '<h1>Database connection failed</h1>';
    echo '<p>' . $message . '</p>';
    if (is_vercel()) {
        echo '<p>Connect <a href="https://neon.tech">Neon</a> to your Vercel project (Storage → Neon, or Neon dashboard → Integrations → Vercel). That injects <code>DATABASE_URL</code> automatically. Or paste your Neon connection string manually under Project → Settings → Environment Variables, then redeploy.</p>';
    }
    echo '</body></html>';
    exit;
}

if (PHP_SAPI !== 'cli') {
    $secure = is_vercel()
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
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
