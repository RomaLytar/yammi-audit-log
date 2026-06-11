<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Yammi\AuditLog\Application\Action\PruneAuditLogAction;

/** @internal */
final class PruneAuditLogCommand extends Command
{
    protected $signature = 'audit-log:prune
                            {--days= : Days to retain; overrides the configured retention for this run}';

    protected $description = 'Delete audit records older than the configured retention period';

    public function handle(PruneAuditLogAction $prune, ConfigRepository $config): int
    {
        $days = $this->resolveDays($config);

        if ($days <= 0) {
            $this->info('Audit retention is disabled (retention days = 0); nothing pruned.');

            return self::SUCCESS;
        }

        $effective = PruneAuditLogAction::clampDays($days);

        $deleted = $prune($days);

        $this->info("Pruned {$deleted} audit record(s) older than {$effective} day(s).");

        return self::SUCCESS;
    }

    private function resolveDays(ConfigRepository $config): int
    {
        $override = $this->option('days');

        if (is_numeric($override)) {
            return (int) $override;
        }

        $configured = $config->get('audit-log.retention.days', PruneAuditLogAction::DEFAULT_DAYS);

        return is_numeric($configured) ? (int) $configured : PruneAuditLogAction::DEFAULT_DAYS;
    }
}
