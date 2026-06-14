<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service\Definition;

use Yammi\AuditLog\Application\DTO\Settings\SettingDefinitionData;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

/** @internal */
final class UiSettings implements SettingGroupProvider
{
    public function definitions(): array
    {
        return [
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_UI,
                key: 'timezone',
                configPath: 'audit-log.timezone',
                type: SettingType::String,
                default: '',
                label: 'Display timezone',
                description: 'Timestamps on the dashboard are shown in this timezone (e.g. Europe/Kyiv, Asia/Tokyo). Empty = the application timezone; storage is unaffected.',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_UI,
                key: 'ui_enabled',
                configPath: 'audit-log.ui.enabled',
                type: SettingType::Boolean,
                default: false,
                label: 'Bundled dashboard',
                description: 'Serves the audit UI under the configured path. Off by default — hosts embedding the data via the facade never expose it. Toggle from the console with audit-log:ui enable|disable; applies from the next request.',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_UI,
                key: 'ui_throttle',
                configPath: 'audit-log.ui.throttle',
                type: SettingType::String,
                default: '60,1',
                label: 'Rate limit',
                description: 'Requests,minutes for the dashboard routes (e.g. 60,1). Empty disables the limit. Applies from the next deploy/restart.',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_UI,
                key: 'jobs_monitor_url',
                configPath: 'audit-log.integrations.jobs_monitor.url',
                type: SettingType::String,
                default: '',
                label: 'JobsMonitor URL',
                description: 'Base URL or path of the Yammi JobsMonitor dashboard. When set, job actors link straight to the monitor. Empty = no links.',
            ),
        ];
    }
}
