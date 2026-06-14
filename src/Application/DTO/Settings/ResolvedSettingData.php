<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO\Settings;

/** @internal */
final class ResolvedSettingData
{
    /**
     * @param  bool|int|string|list<string>  $value
     */
    public function __construct(
        public readonly SettingDefinitionData $definition,
        public readonly bool|int|string|array $value,
    ) {}

    public function inputValue(): string
    {
        return $this->definition->type->serialize($this->value);
    }
}
