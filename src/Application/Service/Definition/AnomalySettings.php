<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service\Definition;

use Yammi\AuditLog\Application\DTO\Settings\SettingDefinitionData;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

/** @internal */
final class AnomalySettings implements SettingGroupProvider
{
    public function definitions(): array
    {
        return [
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_ANOMALIES,
                key: 'anomalies_rate_threshold',
                configPath: 'audit-log.anomalies.rate_threshold',
                type: SettingType::Integer,
                default: 200,
                label: 'Change-burst threshold',
                description: 'Flag an actor with more changes than this inside the scan window. 0 turns the rule off.',
                min: 0,
                max: 1000000,
                suffix: 'changes',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_ANOMALIES,
                key: 'anomalies_delete_threshold',
                configPath: 'audit-log.anomalies.delete_threshold',
                type: SettingType::Integer,
                default: 25,
                label: 'Mass-delete threshold',
                description: 'Flag an actor deleting more records than this inside the scan window. 0 turns the rule off.',
                min: 0,
                max: 1000000,
                suffix: 'deletions',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_ANOMALIES,
                key: 'anomalies_cascade_threshold',
                configPath: 'audit-log.anomalies.cascade_threshold',
                type: SettingType::Integer,
                default: 150,
                label: 'Cascade-weight threshold',
                description: 'Flag a single correlation (one request to job chain) that produced more changes than this, a possible write-amplification or N+1-style cascade. 0 turns the rule off.',
                min: 0,
                max: 1000000,
                suffix: 'changes',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_ANOMALIES,
                key: 'anomalies_off_hours',
                configPath: 'audit-log.anomalies.off_hours',
                type: SettingType::CsvList,
                default: [],
                label: 'Off-hours range',
                description: 'Two hours (0-23) as "from,to", e.g. 0,5 flags user changes between 00:00 and 05:59; 22,5 wraps midnight. Empty turns the rule off.',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_ANOMALIES,
                key: 'anomalies_cron',
                configPath: 'audit-log.anomalies.cron',
                type: SettingType::String,
                default: '',
                label: 'Automatic scan',
                description: 'Cron for audit-log:detect-anomalies. Findings fire the AnomalyDetected event and mail the alert recipients. Applies from the next deploy/restart. Empty = run it manually or from the Anomalies page.',
                options: [
                    '' => 'Off — run manually',
                    '*/15 * * * *' => 'Every 15 minutes',
                    '0 * * * *' => 'Every hour',
                    '0 */6 * * *' => 'Every 6 hours',
                    '0 8 * * *' => 'Daily at 08:00',
                ],
            ),
        ];
    }
}
