<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * The single-row head of the integrity hash chain. Locking this always-present
 * row serialises concurrent writers, so even the first insert into an empty
 * table cannot fork the chain.
 *
 * @property int $id
 * @property string|null $last_hash
 *
 * @internal
 */
final class AuditChainStateModel extends Model
{
    public const ROW_ID = 1;

    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'audit_log_chain_state';

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'last_hash',
    ];

    public function getConnectionName(): ?string
    {
        $connection = config('audit-log.database.connection');

        return is_string($connection) ? $connection : null;
    }
}
