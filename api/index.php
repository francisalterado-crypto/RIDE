<?php

declare(strict_types=1);

/**
 * Vercel serverless entry. All non-asset requests are routed here.
 */
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__) . '/public/index.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($path === '/api' || $path === '/api/' || $path === '/api/index.php') {
    $_SERVER['REQUEST_URI'] = '/' . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
        ? '?' . $_SERVER['QUERY_STRING']
        : '');
}

require dirname(__DIR__) . '/public/index.php';
