<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action;

use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Domain\Settings\Repository\GeneralSettingRepository;

/** @internal */
final class ResetSettingsAction
{
    public function __construct(
        private readonly GeneralSettingRepository $settings,
        private readonly SettingRegistry $registry,
    ) {}

    public function __invoke(): void
    {
        foreach ($this->registry->all() as $definition) {
            $this->settings->remove($definition->group, $definition->key);
        }
    }
}
