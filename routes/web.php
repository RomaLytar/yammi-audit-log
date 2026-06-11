<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yammi\AuditLog\Infrastructure\Http\Controller\DashboardController;
use Yammi\AuditLog\Infrastructure\Http\Controller\DatabaseTransferController;
use Yammi\AuditLog\Infrastructure\Http\Controller\ExportController;
use Yammi\AuditLog\Infrastructure\Http\Controller\NoiseController;
use Yammi\AuditLog\Infrastructure\Http\Controller\SettingsController;
use Yammi\AuditLog\Infrastructure\Http\Controller\TraceController;

Route::get('/', DashboardController::class)->name('audit-log.dashboard');
Route::get('/export', ExportController::class)->name('audit-log.export');
Route::get('/noise', NoiseController::class)->name('audit-log.noise');
Route::get('/trace/{correlation}', TraceController::class)
    ->whereUuid('correlation')
    ->name('audit-log.trace');
Route::get('/settings', [SettingsController::class, 'index'])->name('audit-log.settings');
Route::post('/settings', [SettingsController::class, 'update'])->name('audit-log.settings.update');
Route::post('/settings/reset', [SettingsController::class, 'reset'])->name('audit-log.settings.reset');
Route::post('/settings/database/transfer', DatabaseTransferController::class)->name('audit-log.settings.transfer');
