<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Settings;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Yammi\AuditLog\Application\DTO\ResolvedSettingData;
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
     * @return list<ResolvedSettingData>
     */
    public function all(): array
    {
        $resolved = [];

        foreach ($this->registry->all() as $definition) {
            $value = $this->config->get($definition->configPath, $definition->default);

            $resolved[] = new ResolvedSettingData(
                $definition,
                is_scalar($value) ? $definition->type->cast((string) $value) : $definition->default,
            );
        }

        return $resolved;
    }
}
