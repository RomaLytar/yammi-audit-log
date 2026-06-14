<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service\Definition;

use Yammi\AuditLog\Application\DTO\Settings\SettingDefinitionData;

/** @internal */
interface SettingGroupProvider
{
    /**
     * @return list<SettingDefinitionData>
     */
    public function definitions(): array;
}
