<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service\Definition;

use Yammi\AuditLog\Application\DTO\Settings\SettingDefinitionData;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

/** @internal */
final class AlertSettings implements SettingGroupProvider
{
    public function definitions(): array
    {
        return [
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_ALERTS,
                key: 'alerts_mail_to',
                configPath: 'audit-log.alerts.mail_to',
                type: SettingType::CsvList,
                default: [],
                label: 'Mail recipients',
                description: 'Comma-separated addresses for sensitive-change alerts and anomaly summaries. Empty = no mail.',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_ALERTS,
                key: 'alerts_slack_webhook_url',
                configPath: 'audit-log.alerts.slack_webhook_url',
                type: SettingType::String,
                default: '',
                label: 'Slack webhook URL',
                description: 'Slack incoming-webhook URL. Alerts and anomaly summaries arrive as Block Kit messages with a deep link into the dashboard. Empty = off.',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_ALERTS,
                key: 'alerts_webhook_url',
                configPath: 'audit-log.alerts.webhook.url',
                type: SettingType::String,
                default: '',
                label: 'Webhook URL',
                description: 'Generic JSON webhook for incident routers and automation hubs. Signed with HMAC-SHA256 when AUDIT_LOG_WEBHOOK_SECRET is set (the secret itself stays in the env on purpose). Empty = off.',
            ),
        ];
    }
}
