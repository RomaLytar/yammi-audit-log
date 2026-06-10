<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yammi\AuditLog\Infrastructure\Http\Controller\DashboardController;

Route::get('/', DashboardController::class)->name('audit-log.dashboard');
