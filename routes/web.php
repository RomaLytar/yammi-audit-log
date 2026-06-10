<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yammi\AuditLog\Infrastructure\Http\Controller\DashboardController;
use Yammi\AuditLog\Infrastructure\Http\Controller\NoiseController;
use Yammi\AuditLog\Infrastructure\Http\Controller\TraceController;

Route::get('/', DashboardController::class)->name('audit-log.dashboard');
Route::get('/noise', NoiseController::class)->name('audit-log.noise');
Route::get('/trace/{correlation}', TraceController::class)
    ->whereUuid('correlation')
    ->name('audit-log.trace');
