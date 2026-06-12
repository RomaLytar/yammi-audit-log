<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Events;

use Yammi\AuditLog\Application\DTO\TimelineEntryData;

/**
 * Fired when a recorded change matches one of the audit-log.alerts.rules.
 * Listen to it to push the alert into your own channels (Slack, webhooks…);
 * the package also mails the configured recipients out of the box.
 */
final class SensitiveChangeRecorded
{
    /**
     * @param  array<string, mixed>  $rule
     */
    public function __construct(
        public readonly TimelineEntryData $entry,
        public readonly array $rule,
    ) {}
}
