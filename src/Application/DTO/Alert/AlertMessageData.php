<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO\Alert;

final class AlertMessageData
{
    public const KIND_SENSITIVE_CHANGE = 'sensitive_change';

    public const KIND_ANOMALY = 'anomaly';

    /**
     * @param  list<string>  $lines
     * @param  array<string, scalar|null>  $context
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $title,
        public readonly array $lines,
        public readonly string $occurredAt,
        public readonly ?string $deepLink = null,
        public readonly array $context = [],
    ) {}
}
