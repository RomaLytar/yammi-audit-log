<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console;

use DateInterval;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Yammi\AuditLog\Application\Action\PruneAuditLogAction;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Infrastructure\Console\Support\ArchiveDisk;
use Yammi\AuditLog\Infrastructure\Console\Support\RetentionWindow;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;

/** @internal */
final class ArchiveAuditLogCommand extends Command
{
    private const CHUNK = 500;

    protected $signature = 'audit-log:archive
                            {--days= : Archive records older than this many days (defaults to the retention window)}
                            {--disk= : Filesystem disk to write to (defaults to audit-log.archive.disk)}
                            {--then-prune : Delete the archived records afterwards}';

    protected $description = 'Write expiring audit records to an NDJSON archive before retention deletes them';

    public function handle(
        ConfigRepository $config,
        FilesystemFactory $storage,
        Clock $clock,
        PruneAuditLogAction $prune,
    ): int {
        $days = (new RetentionWindow)->days($this->option('days'), $config);

        if ($days <= 0) {
            $this->info('Retention is disabled and no --days given; nothing to archive.');

            return self::SUCCESS;
        }

        $days = PruneAuditLogAction::clampDays($days);
        $cutoff = $clock->now()->sub(new DateInterval('P'.$days.'D'));

        $disk = $storage->disk((new ArchiveDisk)->name($this->option('disk'), $config));

        $path = 'audit-log/audit-archive-'.$clock->now()->format('Ymd-His').'.ndjson';
        $archived = 0;

        AuditRecordModel::query()
            ->withoutGlobalScopes()
            ->where('occurred_at', '<', $cutoff->format('Y-m-d H:i:s'))
            ->orderBy('id')
            ->chunk(self::CHUNK, function ($models) use ($disk, $path, &$archived): void {
                $lines = '';

                foreach ($models as $model) {
                    $lines .= json_encode($model->getAttributes())."\n";
                }

                $archived === 0 ? $disk->put($path, $lines) : $disk->append($path, rtrim($lines, "\n"));
                $archived += count($models);
            });

        if ($archived === 0) {
            $this->info("No records older than {$days} day(s); nothing archived.");

            return self::SUCCESS;
        }

        $this->info("Archived {$archived} record(s) to {$path}.");

        if ((bool) $this->option('then-prune')) {
            $deleted = $prune($days);
            $this->info("Pruned {$deleted} archived record(s).");
        }

        return self::SUCCESS;
    }
}
