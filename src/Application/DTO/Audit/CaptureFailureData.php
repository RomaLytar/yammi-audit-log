<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO\Audit;

final class CaptureFailureData
{
    public function __construct(
        public readonly ?string $auditableType,
        public readonly ?string $event,
        public readonly string $exception,
        public readonly string $message,
        public readonly string $occurredAt,
    ) {}

    public function model(): string
    {
        if ($this->auditableType === null) {
            return 'unknown';
        }

        $parts = explode('\\', $this->auditableType);

        return end($parts) ?: $this->auditableType;
    }
}
