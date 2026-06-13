<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Transfer;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Collection;
use Yammi\AuditLog\Application\Contract\AuditDataTransferrer;
use Yammi\AuditLog\Application\DTO\Transfer\TransferResultData;

/** @internal */
final class EloquentAuditDataTransferrer implements AuditDataTransferrer
{
    private const CHUNK = 500;

    private const SETTINGS_TABLE = 'audit_log_settings';

    public function __construct(
        private readonly ConnectionResolverInterface $db,
        private readonly string $table,
    ) {}

    public function transfer(string $from, string $to, bool $deleteSource): TransferResultData
    {
        $rowsMoved = 0;

        foreach ($this->tables() as $table) {
            $rowsMoved += $this->moveTable($table, $from, $to);
        }

        if ($deleteSource) {
            $this->dropSource($from);
        }

        return new TransferResultData($rowsMoved);
    }

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        return [$this->table, self::SETTINGS_TABLE];
    }

    private function moveTable(string $table, string $from, string $to): int
    {
        $source = $this->db->connection($from);

        if ($source instanceof Connection && ! $source->getSchemaBuilder()->hasTable($table)) {
            return 0;
        }

        $moved = 0;

        $this->db->connection($from)
            ->table($table)
            ->orderBy('id')
            ->chunk(self::CHUNK, function (Collection $rows) use ($to, $table, &$moved): void {
                $data = array_map(static fn (object $row): array => (array) $row, $rows->all());

                if ($data === []) {
                    return;
                }

                $this->db->connection($to)->table($table)->insertOrIgnore($data);
                $moved += count($data);
            });

        return $moved;
    }

    private function dropSource(string $from): void
    {
        $connection = $this->db->connection($from);

        if (! $connection instanceof Connection) {
            return;
        }

        $schema = $connection->getSchemaBuilder();

        foreach ($this->tables() as $table) {
            $schema->dropIfExists($table);
        }

        if ($schema->hasTable('migrations')) {
            $connection->table('migrations')
                ->where('migration', 'like', '%audit_log%')
                ->delete();
        }
    }
}
