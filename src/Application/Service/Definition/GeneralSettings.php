<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service\Definition;

use Yammi\AuditLog\Application\Action\Retention\PruneAuditLogAction;
use Yammi\AuditLog\Application\DTO\Settings\SettingDefinitionData;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

/** @internal */
final class GeneralSettings implements SettingGroupProvider
{
    public function definitions(): array
    {
        return [
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_GENERAL,
                key: 'enabled',
                configPath: 'audit-log.enabled',
                type: SettingType::Boolean,
                default: true,
                label: 'Capture changes',
                description: 'Master switch for recording Eloquent changes. Turning it off stops new records; existing history stays.',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_GENERAL,
                key: 'retention_days',
                configPath: 'audit-log.retention.days',
                type: SettingType::Integer,
                default: PruneAuditLogAction::DEFAULT_DAYS,
                label: 'Data retention (days)',
                description: 'Audit records older than this are deleted by the daily audit-log:prune run. Minimum 7 days. Audit data is PII — keep the window as short as your compliance allows.',
                min: PruneAuditLogAction::MIN_DAYS,
                max: PruneAuditLogAction::MAX_DAYS,
                suffix: 'days',
                options: [
                    '7' => '7 days',
                    '30' => '30 days',
                    '90' => '90 days',
                    '180' => '180 days (default)',
                    '360' => '360 days',
                ],
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_GENERAL,
                key: 'prune_schedule_enabled',
                configPath: 'audit-log.retention.schedule.enabled',
                type: SettingType::Boolean,
                default: true,
                label: 'Automatic daily pruning',
                description: 'Runs audit-log:prune every day on the cron below. Disable only if you prune from your own scheduler.',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_GENERAL,
                key: 'prune_cron',
                configPath: 'audit-log.retention.schedule.cron',
                type: SettingType::String,
                default: '0 3 * * *',
                label: 'Prune schedule',
                description: 'When the prune runs (cron: minute hour day month weekday). Applies from the next deploy/restart, because schedules are registered at boot.',
                options: [
                    '0 3 * * *' => 'Daily at 03:00',
                    '0 0 * * *' => 'Daily at midnight',
                    '0 */12 * * *' => 'Every 12 hours',
                    '0 */6 * * *' => 'Every 6 hours',
                    '0 * * * *' => 'Every hour',
                    '0 4 * * 0' => 'Weekly, Sunday 04:00',
                ],
            ),
        ];
    }
}
