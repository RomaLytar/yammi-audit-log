<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yammi\AuditLog\Infrastructure\Http\Controller\Api\AuditLogApiController;

Route::get('/changes', [AuditLogApiController::class, 'changes'])->name('audit-log.api.changes');
Route::get('/noise', [AuditLogApiController::class, 'noise'])->name('audit-log.api.noise');
Route::get('/chain/{correlation}', [AuditLogApiController::class, 'chain'])
    ->whereUuid('correlation')
    ->name('audit-log.api.chain');
Route::get('/stats', [AuditLogApiController::class, 'stats'])->name('audit-log.api.stats');
Route::get('/timeline', [AuditLogApiController::class, 'timeline'])->name('audit-log.api.timeline');
Route::get('/state', [AuditLogApiController::class, 'state'])->name('audit-log.api.state');
Route::get('/record-view', [AuditLogApiController::class, 'recordView'])->name('audit-log.api.record-view');
Route::get('/subject-report', [AuditLogApiController::class, 'subjectReport'])->name('audit-log.api.subject-report');
Route::get('/anomalies', [AuditLogApiController::class, 'anomalies'])->name('audit-log.api.anomalies');
