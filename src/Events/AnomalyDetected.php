<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Events;

use Yammi\AuditLog\Application\DTO\Anomaly\AnomalyData;

/**
 * Fired for every finding of audit-log:detect-anomalies — an unusual burst
 * of changes, a mass deletion or off-hours user activity. Listen to it to
 * push the finding into your own channels (Slack, webhooks…); the package
 * also mails the audit-log.alerts.mail_to recipients out of the box.
 */
final class AnomalyDetected
{
    public function __construct(
        public readonly AnomalyData $anomaly,
    ) {}
}
