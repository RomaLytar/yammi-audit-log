<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yammi\AuditLog\Infrastructure\Http\Controller\DashboardController;
use Yammi\AuditLog\Infrastructure\Http\Controller\TraceController;

Route::get('/', DashboardController::class)->name('audit-log.dashboard');
Route::get('/trace/{correlation}', TraceController::class)->name('audit-log.trace');
