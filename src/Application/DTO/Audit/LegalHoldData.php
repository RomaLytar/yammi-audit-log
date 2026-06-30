<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO\Audit;

final class LegalHoldData
{
    public function __construct(
        public readonly string $auditableType,
        public readonly string $auditableId,
        public readonly ?string $reason,
        public readonly ?string $placedAt,
    ) {}

    public function model(): string
    {
        $parts = explode('\\', $this->auditableType);

        return end($parts) ?: $this->auditableType;
    }
}
