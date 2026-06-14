<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Yammi\AuditLog\Application\Action\Retention\PruneAuditLogAction;
use Yammi\AuditLog\Infrastructure\Console\Support\RetentionWindow;

/** @internal */
final class PruneAuditLogCommand extends Command
{
    protected $signature = 'audit-log:prune
                            {--days= : Days to retain; overrides the configured retention for this run}';

    protected $description = 'Delete audit records older than the configured retention period';

    public function handle(PruneAuditLogAction $prune, ConfigRepository $config): int
    {
        $days = (new RetentionWindow)->days($this->option('days'), $config);

        if ($days <= 0) {
            $this->info('Audit retention is disabled (retention days = 0); nothing pruned.');

            return self::SUCCESS;
        }

        $effective = PruneAuditLogAction::clampDays($days);

        $deleted = $prune($days);

        $this->info("Pruned {$deleted} audit record(s) older than {$effective} day(s).");

        return self::SUCCESS;
    }
}
