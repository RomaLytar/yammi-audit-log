<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract;

use Yammi\AuditLog\Application\DTO\AlertMessageData;

/**
 * A transport that delivers an audit alert to an external system (Slack,
 * a signed webhook, ...). Implementations may throw on transport errors;
 * the dispatcher logs and continues — alerting must never break capture.
 */
interface AlertChannel
{
    public function name(): string;

    public function send(AlertMessageData $message): void;
}
