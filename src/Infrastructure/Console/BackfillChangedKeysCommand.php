<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console;

use Illuminate\Console\Command;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditChangedKeyModel;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\ChangedKeyRows;

/**
 * Populates the changed-keys index for records written before the index
 * existed. It is chunked and resumable: records that already have keys are
 * skipped and inserts ignore duplicates, so a re-run never doubles rows.
 *
 * @internal
 */
final class BackfillChangedKeysCommand extends Command
{
    private const CHUNK = 1000;

    protected $signature = 'audit-log:backfill-changed-keys
                            {--chunk= : Records to scan per batch}';

    protected $description = 'Build the changed-keys index for existing audit records';

    public function handle(): int
    {
        $chunk = $this->chunkSize();
        $lastId = 0;
        $scanned = 0;
        $indexed = 0;

        do {
            $records = AuditRecordModel::query()
                ->withoutGlobalScopes()
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($chunk)
                ->get(['id', 'changes']);

            if ($records->isEmpty()) {
                break;
            }

            $ids = $records->pluck('id')->all();

            $alreadyIndexed = AuditChangedKeyModel::query()
                ->whereIn('audit_id', $ids)
                ->distinct()
                ->pluck('audit_id')
                ->all();

            $skip = [];

            foreach ($alreadyIndexed as $indexedId) {
                $skip[(int) $indexedId] = true;
            }

            $rows = [];

            foreach ($records as $record) {
                $id = (int) $record->getKey();
                $lastId = $id;
                $scanned++;

                if (isset($skip[$id])) {
                    continue;
                }

                $rows = [...$rows, ...ChangedKeyRows::build($id, $record->getAttribute('changes'))];
            }

            foreach (array_chunk($rows, 500) as $batch) {
                AuditChangedKeyModel::query()->insertOrIgnore($batch);
                $indexed += count($batch);
            }
        } while (count($ids) === $chunk);

        $this->info("Scanned {$scanned} record(s), indexed {$indexed} changed-key row(s).");

        return self::SUCCESS;
    }

    private function chunkSize(): int
    {
        $option = $this->option('chunk');

        return is_numeric($option) ? max(1, (int) $option) : self::CHUNK;
    }
}
