<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Settings;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Yammi\AuditLog\Application\DTO\ResolvedSettingData;
use Yammi\AuditLog\Application\DTO\SettingDefinitionData;
use Yammi\AuditLog\Application\Service\SettingRegistry;

/**
 * Reads the value each setting currently has after env, config and stored
 * overrides — what the dashboard form should display.
 *
 * @internal
 */
final class EffectiveSettingsReader
{
    public function __construct(
        private readonly SettingRegistry $registry,
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @return array<string, list<ResolvedSettingData>>
     */
    public function grouped(): array
    {
        $grouped = [];

        foreach ($this->registry->all() as $definition) {
            $grouped[$definition->group][] = new ResolvedSettingData($definition, $this->effectiveValue($definition));
        }

        return $grouped;
    }

    /**
     * @return bool|int|string|list<string>
     */
    private function effectiveValue(SettingDefinitionData $definition): bool|int|string|array
    {
        $value = $this->config->get($definition->configPath, $definition->default);

        if (is_array($value)) {
            return array_values(array_filter($value, is_string(...)));
        }

        if (is_scalar($value)) {
            return $definition->type->cast((string) $value);
        }

        return $definition->default;
    }
}
