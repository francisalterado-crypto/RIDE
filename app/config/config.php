<?php

declare(strict_types=1);

$env = static function (string $key, ?string $default = null): ?string {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
};

$database = [
    'driver' => 'mysql',
    'host' => $env('DB_HOST') ?? $env('TIDB_HOST') ?? $env('MYSQLHOST') ?? $env('MYSQL_HOST') ?? '127.0.0.1',
    'port' => (int) ($env('DB_PORT') ?? $env('TIDB_PORT') ?? $env('MYSQLPORT') ?? $env('MYSQL_PORT') ?? '3306'),
    'name' => $env('DB_NAME') ?? $env('TIDB_DATABASE') ?? $env('MYSQLDATABASE') ?? $env('MYSQL_DATABASE') ?? 'ride_ims',
    'user' => $env('DB_USER') ?? $env('TIDB_USER') ?? $env('MYSQLUSER') ?? $env('MYSQL_USER') ?? 'root',
    'pass' => $env('DB_PASS') ?? $env('TIDB_PASSWORD') ?? $env('MYSQLPASSWORD') ?? $env('MYSQL_PASSWORD') ?? '',
    'charset' => 'utf8mb4',
    'ssl' => in_array(strtolower((string) ($env('DB_SSL', '') ?? '')), ['1', 'true', 'require', 'required'], true),
    'ssl_ca' => $env('DB_SSL_CA'),
    'ssl_verify' => in_array(strtolower((string) ($env('DB_SSL_VERIFY', '') ?? '')), ['1', 'true'], true),
];

$databaseUrl = $env('DATABASE_URL')
    ?? $env('POSTGRES_URL')
    ?? $env('POSTGRES_PRISMA_URL')
    ?? $env('MYSQL_URL')
    ?? $env('MYSQL_PUBLIC_URL');
if (is_string($databaseUrl) && $databaseUrl !== '') {
    $scheme = strtolower((string) (parse_url($databaseUrl, PHP_URL_SCHEME) ?? ''));
    if (in_array($scheme, ['postgres', 'postgresql', 'mysql'], true)) {
        if (in_array($scheme, ['postgres', 'postgresql'], true)) {
            $database['driver'] = 'pgsql';
        }
    }

    $parts = parse_url($databaseUrl);
    if (is_array($parts) && isset($parts['host'])) {
        $database['host'] = $parts['host'];
        $defaultPort = $database['driver'] === 'pgsql' ? 5432 : 3306;
        $database['port'] = isset($parts['port']) ? (int) $parts['port'] : $defaultPort;
        $database['name'] = ltrim((string) ($parts['path'] ?? '/ride_ims'), '/') ?: 'ride_ims';
        $database['user'] = isset($parts['user']) ? rawurldecode((string) $parts['user']) : $database['user'];
        $database['pass'] = isset($parts['pass']) ? rawurldecode((string) $parts['pass']) : '';
        if (!empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
            $sslMode = strtolower((string) ($query['ssl-mode'] ?? $query['sslmode'] ?? $query['ssl'] ?? ''));
            if ($sslMode !== '' && $sslMode !== 'disable' && $sslMode !== 'false' && $sslMode !== '0') {
                $database['ssl'] = true;
            }
        }
    }
}

$pgHost = $env('PGHOST') ?? $env('POSTGRES_HOST');
if ($pgHost !== null && $pgHost !== '') {
    $database['driver'] = 'pgsql';
    $database['host'] = $pgHost;
    $database['port'] = (int) ($env('PGPORT') ?? $env('POSTGRES_PORT') ?? '5432');
    $database['name'] = $env('PGDATABASE') ?? $env('POSTGRES_DATABASE') ?? $database['name'];
    $database['user'] = $env('PGUSER') ?? $env('POSTGRES_USER') ?? $database['user'];
    $database['pass'] = $env('PGPASSWORD') ?? $env('POSTGRES_PASSWORD') ?? $database['pass'];
    $database['ssl'] = true;
}

$vercel = ($env('VERCEL') === '1' || $env('VERCEL') === 'true');
$tidbHost = $env('TIDB_HOST');
if ($database['driver'] === 'pgsql') {
    $database['ssl'] = $database['ssl'] || $env('DB_SSL') !== 'false';
} elseif ($vercel && $database['host'] !== '127.0.0.1' && $database['host'] !== 'localhost') {
    $database['ssl'] = $database['ssl'] || $env('DB_SSL') !== 'false';
}
if ($tidbHost !== null && $tidbHost !== '') {
    $database['ssl'] = true;
    if ($env('TIDB_PORT') !== null && $env('DB_PORT') === null) {
        $database['port'] = (int) $env('TIDB_PORT');
    }
}

$appUrl = $env('APP_URL');
if ($appUrl === null || $appUrl === '') {
    $vercelHost = $env('VERCEL_PROJECT_PRODUCTION_URL') ?? $env('VERCEL_URL');
    $appUrl = $vercelHost !== null && $vercelHost !== ''
        ? (str_starts_with($vercelHost, 'http') ? $vercelHost : 'https://' . $vercelHost)
        : 'http://localhost/RIDE/public';
}

return [
    'app' => [
        'name' => $env('APP_NAME', 'Research & Extension Monitoring System') ?? 'Research & Extension Monitoring System',
        'url' => rtrim($appUrl, '/'),
        'timezone' => $env('APP_TIMEZONE', 'Asia/Manila') ?? 'Asia/Manila',
    ],
    'database' => $database,
    'session' => [
        'lifetime' => (int) ($env('SESSION_LIFETIME', '7200') ?? '7200'),
    ],
];
