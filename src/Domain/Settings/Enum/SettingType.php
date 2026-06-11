<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Settings\Enum;

enum SettingType: string
{
    case Boolean = 'boolean';
    case Integer = 'integer';
    case String = 'string';

    public function cast(string $raw): bool|int|string
    {
        return match ($this) {
            self::Boolean => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            self::Integer => (int) $raw,
            self::String => $raw,
        };
    }

    public function serialize(bool|int|string $value): string
    {
        return match ($this) {
            self::Boolean => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
