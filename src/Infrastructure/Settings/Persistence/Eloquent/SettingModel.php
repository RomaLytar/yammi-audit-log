<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Settings\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 *
 * @internal
 */
final class SettingModel extends Model
{
    protected $table = 'audit_log_settings';

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];

    public function getConnectionName(): ?string
    {
        $connection = config('audit-log.database.connection');

        return is_string($connection) ? $connection : null;
    }
}
