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

    private const CHAIN_STATE_TABLE = 'audit_log_chain_state';

    public function __construct(
        private readonly ConnectionResolverInterface $db,
        private readonly string $table,
    ) {}

    public function transfer(string $from, string $to, bool $deleteSource): TransferResultData
    {
        $rowsMoved = 0;

        foreach ($this->appendTables() as $table) {
            $rowsMoved += $this->moveTable($table, $from, $to);
        }

        $rowsMoved += $this->moveChangedKeys($from, $to);
        $rowsMoved += $this->moveChainState($from, $to);

        if ($deleteSource) {
            $this->dropSource($from);
        }

        return new TransferResultData($rowsMoved);
    }

    /**
     * Tables whose rows are appended to the freshly migrated (empty)
     * destination with insertOrIgnore.
     *
     * @return list<string>
     */
    private function appendTables(): array
    {
        return [$this->table, self::SETTINGS_TABLE, $this->digestsTable()];
    }

    /**
     * @return list<string>
     */
    private function allTables(): array
    {
        return [...$this->appendTables(), $this->changedKeysTable(), self::CHAIN_STATE_TABLE];
    }

    private function digestsTable(): string
    {
        return $this->table.'_digests';
    }

    private function changedKeysTable(): string
    {
        return $this->table.'_changed_keys';
    }

    /**
     * The changed-keys index has no surrogate id; order by its composite key so
     * the chunked copy is stable, and ignore duplicates on the destination.
     */
    private function moveChangedKeys(string $from, string $to): int
    {
        $table = $this->changedKeysTable();

        if (! $this->sourceHasTable($from, $table)) {
            return 0;
        }

        $moved = 0;

        $this->db->connection($from)
            ->table($table)
            ->orderBy('audit_id')
            ->orderBy('key')
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

    private function moveTable(string $table, string $from, string $to): int
    {
        if (! $this->sourceHasTable($from, $table)) {
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

    /**
     * The chain head is a single fixed-id row that the destination migration
     * already seeded with a null hash, so insertOrIgnore would skip it and
     * leave the chain forked. Upsert it instead, carrying the source head over.
     */
    private function moveChainState(string $from, string $to): int
    {
        if (! $this->sourceHasTable($from, self::CHAIN_STATE_TABLE)) {
            return 0;
        }

        $moved = 0;

        $this->db->connection($from)
            ->table(self::CHAIN_STATE_TABLE)
            ->orderBy('id')
            ->chunk(self::CHUNK, function (Collection $rows) use ($to, &$moved): void {
                foreach ($rows as $row) {
                    $data = (array) $row;
                    $id = $data['id'] ?? null;
                    unset($data['id']);

                    $this->db->connection($to)
                        ->table(self::CHAIN_STATE_TABLE)
                        ->updateOrInsert(['id' => $id], $data);
                    $moved++;
                }
            });

        return $moved;
    }

    private function sourceHasTable(string $from, string $table): bool
    {
        $source = $this->db->connection($from);

        return ! $source instanceof Connection || $source->getSchemaBuilder()->hasTable($table);
    }

    private function dropSource(string $from): void
    {
        $connection = $this->db->connection($from);

        if (! $connection instanceof Connection) {
            return;
        }

        $schema = $connection->getSchemaBuilder();

        foreach ($this->allTables() as $table) {
            $schema->dropIfExists($table);
        }

        if ($schema->hasTable('migrations')) {
            $connection->table('migrations')
                ->where('migration', 'like', '%audit_log%')
                ->delete();
        }
    }
}
