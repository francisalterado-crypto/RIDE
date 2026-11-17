<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;

/**
 * Uptime check for the deployment (GET /api/health): whether the app can reach its database.
 * It is public, so it reports only the status, never connection details or error text.
 */
final class HealthController
{
    public function show(): void
    {
        try {
            Database::pdo()->query('SELECT 1')->fetchColumn();
        } catch (\Throwable $e) {
            error_log('Health check failed: ' . $e->getMessage());
            json_response(['status' => 'error', 'database' => 'unreachable'], 503);
        }

        json_response(['status' => 'ok', 'database' => 'connected']);
    }
}
