<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * One indexed row per (audit record, changed field name). It lets the field
 * filter seek matching records by column name instead of scanning the JSON
 * changes payload of every row.
 *
 * @internal
 */
final class AuditChangedKeyModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function getConnectionName(): ?string
    {
        $connection = config('audit-log.database.connection');

        return is_string($connection) ? $connection : null;
    }

    public function getTable(): string
    {
        $table = config('audit-log.database.table', 'audit_log');

        return (is_string($table) ? $table : 'audit_log').'_changed_keys';
    }
}
