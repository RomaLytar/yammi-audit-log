<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service;

use Yammi\AuditLog\Application\Action\PruneAuditLogAction;
use Yammi\AuditLog\Application\DTO\SettingDefinitionData;
use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

/**
 * The catalogue of operator-editable settings: where each one lives in config,
 * its type, bounds and UI copy. Stored values overlay config at boot.
 *
 * @internal
 */
final class SettingRegistry
{
    public const GROUP_GENERAL = 'general';

    /**
     * @return list<SettingDefinitionData>
     */
    public function all(): array
    {
        return [
            new SettingDefinitionData(
                group: self::GROUP_GENERAL,
                key: 'retention_days',
                configPath: 'audit-log.retention.days',
                type: SettingType::Integer,
                default: PruneAuditLogAction::DEFAULT_DAYS,
                label: 'Data retention (days)',
                description: 'Audit records older than this are deleted by the daily audit-log:prune run. Audit data is PII — keep the window as short as your compliance allows.',
                min: PruneAuditLogAction::MIN_DAYS,
                max: PruneAuditLogAction::MAX_DAYS,
                suffix: 'days',
            ),
            new SettingDefinitionData(
                group: self::GROUP_GENERAL,
                key: 'prune_schedule_enabled',
                configPath: 'audit-log.retention.schedule.enabled',
                type: SettingType::Boolean,
                default: true,
                label: 'Automatic daily pruning',
                description: 'Runs audit-log:prune every day on the configured cron. Disable only if you prune from your own scheduler.',
            ),
        ];
    }

    public function find(string $key): ?SettingDefinitionData
    {
        foreach ($this->all() as $definition) {
            if ($definition->key === $key) {
                return $definition;
            }
        }

        return null;
    }
}
