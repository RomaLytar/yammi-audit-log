<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO;

use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

/** @internal */
final class SettingDefinitionData
{
    public function __construct(
        public readonly string $group,
        public readonly string $key,
        public readonly string $configPath,
        public readonly SettingType $type,
        public readonly bool|int|string $default,
        public readonly string $label,
        public readonly string $description,
        public readonly ?int $min = null,
        public readonly ?int $max = null,
        public readonly ?string $suffix = null,
    ) {}

    public function clamp(bool|int|string $value): bool|int|string
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
