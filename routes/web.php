<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yammi\AuditLog\Infrastructure\Http\Controller\AnomaliesController;
use Yammi\AuditLog\Infrastructure\Http\Controller\DashboardController;
use Yammi\AuditLog\Infrastructure\Http\Controller\DatabaseSettingsController;
use Yammi\AuditLog\Infrastructure\Http\Controller\DatabaseTransferController;
use Yammi\AuditLog\Infrastructure\Http\Controller\ExportController;
use Yammi\AuditLog\Infrastructure\Http\Controller\NoiseController;
use Yammi\AuditLog\Infrastructure\Http\Controller\PlaygroundController;
use Yammi\AuditLog\Infrastructure\Http\Controller\SettingsController;
use Yammi\AuditLog\Infrastructure\Http\Controller\StatsController;
use Yammi\AuditLog\Infrastructure\Http\Controller\TimeMachineController;
use Yammi\AuditLog\Infrastructure\Http\Controller\TraceController;

Route::get('/', DashboardController::class)->name('audit-log.dashboard');
Route::get('/export', ExportController::class)->name('audit-log.export');
Route::get('/noise', NoiseController::class)->name('audit-log.noise');
Route::get('/stats', StatsController::class)->name('audit-log.stats');
Route::get('/anomalies', AnomaliesController::class)->name('audit-log.anomalies');
Route::get('/time-machine', TimeMachineController::class)->name('audit-log.time-machine');
Route::get('/trace/{correlation}', TraceController::class)
    ->whereUuid('correlation')
    ->name('audit-log.trace');
Route::get('/settings', [SettingsController::class, 'index'])->name('audit-log.settings');
Route::get('/settings/general', [SettingsController::class, 'general'])->name('audit-log.settings.general');
Route::get('/settings/docs', [SettingsController::class, 'docs'])->name('audit-log.settings.docs');
Route::post('/settings/general', [SettingsController::class, 'update'])->name('audit-log.settings.update');
Route::post('/settings/general/reset', [SettingsController::class, 'reset'])->name('audit-log.settings.reset');
Route::get('/settings/database', DatabaseSettingsController::class)->name('audit-log.settings.database');
Route::post('/settings/database/transfer', DatabaseTransferController::class)->name('audit-log.settings.transfer');
Route::get('/settings/playground', [PlaygroundController::class, 'index'])->name('audit-log.playground');
Route::post('/settings/playground/execute', [PlaygroundController::class, 'execute'])->name('audit-log.playground.execute');
