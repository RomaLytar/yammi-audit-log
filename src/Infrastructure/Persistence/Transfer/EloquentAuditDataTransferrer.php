<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Transfer;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Collection;
use Yammi\AuditLog\Application\Contract\AuditDataTransferrer;
use Yammi\AuditLog\Application\DTO\TransferResultData;

/** @internal */
final class EloquentAuditDataTransferrer implements AuditDataTransferrer
{
    private const CHUNK = 500;

    public function __construct(
        private readonly ConnectionResolverInterface $db,
        private readonly string $table,
    ) {}

    public function transfer(string $from, string $to, bool $deleteSource): TransferResultData
    {
        $rowsMoved = 0;

        $this->db->connection($from)
            ->table($this->table)
            ->orderBy('id')
            ->chunk(self::CHUNK, function (Collection $rows) use ($to, &$rowsMoved): void {
                $data = array_map(static fn (object $row): array => (array) $row, $rows->all());

                if ($data === []) {
                    return;
                }

                $this->db->connection($to)->table($this->table)->insertOrIgnore($data);
                $rowsMoved += count($data);
            });

        if ($deleteSource) {
            $this->dropSource($from);
        }

        return new TransferResultData($rowsMoved);
    }

    private function dropSource(string $from): void
    {
        $connection = $this->db->connection($from);

        if (! $connection instanceof Connection) {
            return;
        }

        $schema = $connection->getSchemaBuilder();
        $schema->dropIfExists($this->table);

        if ($schema->hasTable('migrations')) {
            $connection->table('migrations')
                ->where('migration', 'like', '%create_audit_log_table%')
                ->delete();
        }
    }
}
