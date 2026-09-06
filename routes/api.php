<?php

declare(strict_types=1);

use App\Controllers\ApiController;

$router->get('/api/health', [ApiController::class, 'health']);
$router->post('/api/login', [ApiController::class, 'login']);
$router->get('/api/proposals', [ApiController::class, 'proposals']);
$router->get('/api/stats', [ApiController::class, 'stats']);
$router->get('/api/projects', [ApiController::class, 'projects']);
$router->get('/api/reports/extension-beneficiaries', [ApiController::class, 'extensionBeneficiaries']);
