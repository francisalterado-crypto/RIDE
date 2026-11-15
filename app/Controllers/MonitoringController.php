<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * The Monitoring page was folded into the dashboards (review queue, pipeline, breakdown, submitters) and the
 * Proposals registry (all submissions with filters). The route stays so old links and bookmarks still work.
 */
final class MonitoringController
{
    public function index(): void
    {
        redirect('dashboard');
    }
}
