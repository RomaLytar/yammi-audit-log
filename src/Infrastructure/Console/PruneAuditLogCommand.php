<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Yammi\AuditLog\Application\Action\PruneAuditLogAction;

final class PruneAuditLogCommand extends Command
{
    protected $signature = 'audit-log:prune';

    protected $description = 'Delete audit records older than the configured retention period';

    public function handle(PruneAuditLogAction $prune, ConfigRepository $config): int
    {
        $days = (int) $config->get('audit-log.retention.days', 0);

        if ($days <= 0) {
            $this->info('Audit retention is disabled (retention.days = 0); nothing pruned.');

            return self::SUCCESS;
        }

        $deleted = $prune($days);

        $this->info("Pruned {$deleted} audit record(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
