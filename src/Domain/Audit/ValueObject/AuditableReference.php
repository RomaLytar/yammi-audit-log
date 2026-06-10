<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\ValueObject;

use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;

final class AuditableReference
{
    public function __construct(
        public readonly string $type,
        public readonly string $id,
    ) {
        if ($type === '') {
            throw InvalidAuditData::emptyValue('auditable type');
        }

        if ($id === '') {
            throw InvalidAuditData::emptyValue('auditable id');
        }
    }

    public static function to(string $type, string|int $id): self
    {
        return new self($type, (string) $id);
    }

    public function equals(self $other): bool
    {
        return $this->type === $other->type && $this->id === $other->id;
    }
}
