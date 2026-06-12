<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service;

use Yammi\AuditLog\Application\Action\PruneAuditLogAction;
use Yammi\AuditLog\Application\DTO\SettingDefinitionData;
use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

/**
 * The catalogue of operator-editable settings: where each one lives in config,
 * its type, bounds and UI copy. Stored values overlay config at boot, so the
 * precedence is: saved setting > published config/env > package default.
 * Bootstrap-critical values (database connection, route path, middleware,
 * gate) stay config-only on purpose — a typo there can lock the dashboard out.
 *
 * @internal
 */
final class SettingRegistry
{
    public const GROUP_GENERAL = 'general';

    public const GROUP_WRITE = 'write';

    public const GROUP_CAPTURE = 'capture';

    public const GROUP_REDACTION = 'redaction';

    public const GROUP_ALERTS = 'alerts';

    public const GROUP_ANOMALIES = 'anomalies';

    public const GROUP_UI = 'ui';

    /**
     * @return list<SettingDefinitionData>
     */
    public function all(): array
    {
        return [
            new SettingDefinitionData(
                group: self::GROUP_GENERAL,
                key: 'enabled',
                configPath: 'audit-log.enabled',
                type: SettingType::Boolean,
                default: true,
                label: 'Capture changes',
                description: 'Master switch for recording Eloquent changes. Turning it off stops new records; existing history stays.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_GENERAL,
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
                group: self::GROUP_GENERAL,
                key: 'prune_schedule_enabled',
                configPath: 'audit-log.retention.schedule.enabled',
                type: SettingType::Boolean,
                default: true,
                label: 'Automatic daily pruning',
                description: 'Runs audit-log:prune every day on the cron below. Disable only if you prune from your own scheduler.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_GENERAL,
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
            new SettingDefinitionData(
                group: self::GROUP_WRITE,
                key: 'write_async',
                configPath: 'audit-log.write.async',
                type: SettingType::Boolean,
                default: false,
                label: 'Async writes',
                description: 'Defer the audit insert to the queue. The actor, origin, correlation and redacted diff are still resolved at the moment of the change.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_WRITE,
                key: 'write_queue',
                configPath: 'audit-log.write.queue',
                type: SettingType::String,
                default: '',
                label: 'Async queue name',
                description: 'Queue for the deferred insert. Empty = the default queue.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_WRITE,
                key: 'integrity_enabled',
                configPath: 'audit-log.integrity.enabled',
                type: SettingType::Boolean,
                default: false,
                label: 'Hash-chain integrity',
                description: 'Chains every stored record to the previous one (sha256), so audit-log:verify can prove the history was not edited. Costs one extra select per insert.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_CAPTURE,
                key: 'ignore_attributes',
                configPath: 'audit-log.capture.ignore_attributes',
                type: SettingType::CsvList,
                default: ['created_at', 'updated_at'],
                label: 'Ignored attributes',
                description: 'Comma-separated attributes dropped from every diff. An update touching only these is flagged as noise.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_CAPTURE,
                key: 'request_context',
                configPath: 'audit-log.capture.request_context',
                type: SettingType::Boolean,
                default: false,
                label: 'Request metadata',
                description: 'Attach ip, url, method and user agent to changes captured during HTTP requests. This is PII — retention applies to it like to everything else.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_REDACTION,
                key: 'redaction_keys',
                configPath: 'audit-log.redaction.keys',
                type: SettingType::CsvList,
                default: ['password', 'remember_token', 'token', 'secret', 'authorization', 'api_key', 'credit_card', 'ssn'],
                label: 'Secret key patterns',
                description: 'Comma-separated, case-insensitive substrings. Any field whose name contains one of these is stored redacted — including inside nested JSON values.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_REDACTION,
                key: 'redaction_placeholder',
                configPath: 'audit-log.redaction.placeholder',
                type: SettingType::String,
                default: '[redacted]',
                label: 'Redaction placeholder',
                description: 'The text stored instead of a secret value.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_ALERTS,
                key: 'alerts_mail_to',
                configPath: 'audit-log.alerts.mail_to',
                type: SettingType::CsvList,
                default: [],
                label: 'Mail recipients',
                description: 'Comma-separated addresses for sensitive-change alerts and anomaly summaries. Empty = no mail.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_ALERTS,
                key: 'alerts_slack_webhook_url',
                configPath: 'audit-log.alerts.slack_webhook_url',
                type: SettingType::String,
                default: '',
                label: 'Slack webhook URL',
                description: 'Slack incoming-webhook URL. Alerts and anomaly summaries arrive as Block Kit messages with a deep link into the dashboard. Empty = off.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_ALERTS,
                key: 'alerts_webhook_url',
                configPath: 'audit-log.alerts.webhook.url',
                type: SettingType::String,
                default: '',
                label: 'Webhook URL',
                description: 'Generic JSON webhook for incident routers and automation hubs. Signed with HMAC-SHA256 when AUDIT_LOG_WEBHOOK_SECRET is set (the secret itself stays in the env on purpose). Empty = off.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_ANOMALIES,
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
                group: self::GROUP_ANOMALIES,
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
                group: self::GROUP_ANOMALIES,
                key: 'anomalies_off_hours',
                configPath: 'audit-log.anomalies.off_hours',
                type: SettingType::CsvList,
                default: [],
                label: 'Off-hours range',
                description: 'Two hours (0-23) as "from,to", e.g. 0,5 flags user changes between 00:00 and 05:59; 22,5 wraps midnight. Empty turns the rule off.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_ANOMALIES,
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
            new SettingDefinitionData(
                group: self::GROUP_UI,
                key: 'timezone',
                configPath: 'audit-log.timezone',
                type: SettingType::String,
                default: '',
                label: 'Display timezone',
                description: 'Timestamps on the dashboard are shown in this timezone (e.g. Europe/Kyiv, Asia/Tokyo). Empty = the application timezone; storage is unaffected.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_UI,
                key: 'ui_enabled',
                configPath: 'audit-log.ui.enabled',
                type: SettingType::Boolean,
                default: false,
                label: 'Bundled dashboard',
                description: 'Serves the audit UI under the configured path. Off by default — hosts embedding the data via the facade never expose it. Toggle from the console with audit-log:ui enable|disable; applies from the next request.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_UI,
                key: 'ui_throttle',
                configPath: 'audit-log.ui.throttle',
                type: SettingType::String,
                default: '60,1',
                label: 'Rate limit',
                description: 'Requests,minutes for the dashboard routes (e.g. 60,1). Empty disables the limit. Applies from the next deploy/restart.',
            ),
            new SettingDefinitionData(
                group: self::GROUP_UI,
                key: 'jobs_monitor_url',
                configPath: 'audit-log.integrations.jobs_monitor.url',
                type: SettingType::String,
                default: '',
                label: 'JobsMonitor URL',
                description: 'Base URL or path of the Yammi JobsMonitor dashboard. When set, job actors link straight to the monitor. Empty = no links.',
            ),
        ];
    }

    /**
     * @return array<string, list<SettingDefinitionData>>
     */
    public function grouped(): array
    {
        $grouped = [];

        foreach ($this->all() as $definition) {
            $grouped[$definition->group][] = $definition;
        }

        return $grouped;
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
