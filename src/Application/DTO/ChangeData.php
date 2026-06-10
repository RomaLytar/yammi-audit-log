<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO;

use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

/** @internal */
final class ChangeData
{
    /**
     * @param  array<string, scalar|array<array-key, mixed>|null>  $before
     * @param  array<string, scalar|array<array-key, mixed>|null>  $after
     */
    public function __construct(
        public readonly string $auditableType,
        public readonly string $auditableId,
        public readonly ChangeType $event,
        public readonly array $before,
        public readonly array $after,
    ) {}

    public function reference(): AuditableReference
    {
        return new AuditableReference($this->auditableType, $this->auditableId);
    }
}
