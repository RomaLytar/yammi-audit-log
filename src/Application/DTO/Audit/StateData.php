<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO\Audit;

final class StateData
{
    /**
     * @param  array<string, scalar|array<array-key, mixed>|null>  $attributes
     */
    public function __construct(
        public readonly string $auditableType,
        public readonly string $auditableId,
        public readonly string $at,
        public readonly bool $existed,
        public readonly array $attributes,
        public readonly int $appliedChanges,
        public readonly ?string $lastChangeAt,
        public readonly bool $truncated = false,
    ) {}

    public function model(): string
    {
        $parts = explode('\\', $this->auditableType);

        return end($parts) ?: $this->auditableType;
    }
}
