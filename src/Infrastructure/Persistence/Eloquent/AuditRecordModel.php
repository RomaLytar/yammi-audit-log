<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 *
 * @internal
 */
final class AuditRecordModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'changes' => 'array',
        'labels' => 'array',
        'is_noise' => 'boolean',
    ];

    public function getConnectionName(): ?string
    {
        $connection = config('audit-log.database.connection');

        return is_string($connection) ? $connection : null;
    }

    public function getTable(): string
    {
        $table = config('audit-log.database.table', 'audit_log');

        return is_string($table) ? $table : 'audit_log';
    }
}
