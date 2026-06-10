<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\ValueObject;

use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;

final class FieldDiff
{
    private const MAX_VALUE_LENGTH = 65535;

    public readonly string $field;

    /** @var scalar|array<array-key, mixed>|null */
    public readonly string|int|float|bool|array|null $old;

    /** @var scalar|array<array-key, mixed>|null */
    public readonly string|int|float|bool|array|null $new;

    /**
     * @param  scalar|array<array-key, mixed>|null  $old
     * @param  scalar|array<array-key, mixed>|null  $new
     */
    public function __construct(string $field, string|int|float|bool|array|null $old, string|int|float|bool|array|null $new)
    {
        if ($field === '') {
            throw InvalidAuditData::emptyValue('field name');
        }

        $this->field = $field;
        $this->old = $this->cap($old);
        $this->new = $this->cap($new);
    }

    public function changed(): bool
    {
        return $this->old !== $this->new;
    }

    /**
     * @param  scalar|array<array-key, mixed>|null  $value
     * @return scalar|array<array-key, mixed>|null
     */
    private function cap(string|int|float|bool|array|null $value): string|int|float|bool|array|null
    {
        if (is_string($value) && mb_strlen($value) > self::MAX_VALUE_LENGTH) {
            return $this->truncate($value);
        }

        if (is_array($value)) {
            $encoded = json_encode($value);

            if ($encoded !== false && strlen($encoded) > self::MAX_VALUE_LENGTH) {
                return $this->truncate($encoded);
            }
        }

        return $value;
    }

    private function truncate(string $value): string
    {
        return mb_substr($value, 0, self::MAX_VALUE_LENGTH).'… (truncated)';
    }
}
