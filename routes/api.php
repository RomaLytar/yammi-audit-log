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
