<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Settings\Enum;

enum SettingType: string
{
    case Boolean = 'boolean';
    case Integer = 'integer';
    case String = 'string';
    case CsvList = 'csv_list';

    /**
     * @return bool|int|string|list<string>
     */
    public function cast(string $raw): bool|int|string|array
    {
        return match ($this) {
            self::Boolean => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            self::Integer => (int) $raw,
            self::String => $raw,
            self::CsvList => array_values(array_filter(array_map(trim(...), explode(',', $raw)), static fn (string $item): bool => $item !== '')),
        };
    }

    /**
     * @param  bool|int|string|array<array-key, mixed>  $value
     */
    public function serialize(bool|int|string|array $value): string
    {
        if ($this === self::Boolean) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return implode(', ', array_filter($value, is_string(...)));
        }

        return (string) $value;
    }
}
