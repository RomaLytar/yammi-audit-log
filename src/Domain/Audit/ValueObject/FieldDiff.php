<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\ValueObject;

use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;

final class FieldDiff
{
    /**
     * @param  scalar|array<array-key, mixed>|null  $old
     * @param  scalar|array<array-key, mixed>|null  $new
     */
    public function __construct(
        public readonly string $field,
        public readonly string|int|float|bool|array|null $old,
        public readonly string|int|float|bool|array|null $new,
    ) {
        if ($field === '') {
            throw InvalidAuditData::emptyValue('field name');
        }
    }

    public function changed(): bool
    {
        return $this->old !== $this->new;
    }
}
