<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;

/**
 * Inserts an already-built audit row. The record is fully resolved at capture
 * time (actor, origin, correlation, redaction) — only the insert is deferred,
 * so attribution never depends on worker state.
 *
 * @internal
 */
final class PersistAuditRecordJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * @param  array<string, mixed>  $row
     */
    public function __construct(
        public readonly array $row,
    ) {}

    public function handle(): void
    {
        AuditRecordModel::query()->create($this->row);
    }
}
