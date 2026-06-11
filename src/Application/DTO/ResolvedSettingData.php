<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO;

/** @internal */
final class ResolvedSettingData
{
    public function __construct(
        public readonly SettingDefinitionData $definition,
        public readonly bool|int|string $value,
    ) {}
}
