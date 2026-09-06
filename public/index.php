<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (str_starts_with($path, '/assets/')) {
    $assetsRoot = realpath(__DIR__ . '/assets');
    $file = realpath(__DIR__ . $path);
    if ($assetsRoot !== false && $file !== false && str_starts_with($file, $assetsRoot) && is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            default => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: ' . (string) filesize($file));
        readfile($file);
        exit;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;

$router = new Router();
require dirname(__DIR__) . '/routes/web.php';
require dirname(__DIR__) . '/routes/api.php';

$router->dispatch();
