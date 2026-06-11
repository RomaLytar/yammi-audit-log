<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO;

use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

/** @internal */
final class SettingDefinitionData
{
    /**
     * @param  bool|int|string|list<string>  $default
     */
    public function __construct(
        public readonly string $group,
        public readonly string $key,
        public readonly string $configPath,
        public readonly SettingType $type,
        public readonly bool|int|string|array $default,
        public readonly string $label,
        public readonly string $description,
        public readonly ?int $min = null,
        public readonly ?int $max = null,
        public readonly ?string $suffix = null,
    ) {}

    /**
     * @param  bool|int|string|array<array-key, mixed>  $value
     * @return bool|int|string|array<array-key, mixed>
     */
    public function clamp(bool|int|string|array $value): bool|int|string|array
    {
        if (! is_int($value)) {
            return $value;
        }

        if ($this->min !== null && $value < $this->min) {
            return $this->min;
        }

        if ($this->max !== null && $value > $this->max) {
            return $this->max;
        }

        return $value;
    }
}
