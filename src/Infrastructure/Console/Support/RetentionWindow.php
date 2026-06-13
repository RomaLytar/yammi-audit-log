<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Yammi\AuditLog\Application\Action\PruneAuditLogAction;

/**
 * Resolves the retention window shared by the prune and archive commands: a
 * numeric --days override wins, otherwise the configured retention, otherwise
 * the package default.
 *
 * @internal
 */
final class RetentionWindow
{
    public function days(mixed $option, ConfigRepository $config): int
    {
        if (is_numeric($option)) {
            return (int) $option;
        }

        $configured = $config->get('audit-log.retention.days', PruneAuditLogAction::DEFAULT_DAYS);

        return is_numeric($configured) ? (int) $configured : PruneAuditLogAction::DEFAULT_DAYS;
    }
}
