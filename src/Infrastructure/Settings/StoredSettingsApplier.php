<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Settings;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Throwable;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Domain\Settings\Repository\GeneralSettingRepository;

/**
 * Overlays operator-saved settings onto config at boot: stored value beats
 * config beats package default. Fails closed when the settings table is not
 * reachable yet (fresh install, mid-migration).
 *
 * @internal
 */
final class StoredSettingsApplier
{
    public function __construct(
        private readonly SettingRegistry $registry,
        private readonly GeneralSettingRepository $settings,
        private readonly ConfigRepository $config,
    ) {}

    public function apply(): void
    {
        try {
            $stored = $this->settings->all();
        } catch (Throwable) {
            return;
        }

        foreach ($this->registry->all() as $definition) {
            $raw = $stored[$definition->group][$definition->key] ?? null;

            if ($raw !== null) {
                $this->config->set($definition->configPath, $definition->clamp($definition->type->cast($raw)));
            }
        }
    }
}
