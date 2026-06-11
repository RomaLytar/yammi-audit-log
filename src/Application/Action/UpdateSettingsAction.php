<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action;

use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Domain\Settings\Repository\GeneralSettingRepository;

/** @internal */
final class UpdateSettingsAction
{
    public function __construct(
        private readonly GeneralSettingRepository $settings,
        private readonly SettingRegistry $registry,
    ) {}

    /**
     * Persist the submitted values for known settings, clamped to their
     * declared bounds. Unknown keys are ignored.
     *
     * @param  array<string, bool|int|string>  $values
     */
    public function __invoke(array $values): void
    {
        foreach ($this->registry->all() as $definition) {
            if (! array_key_exists($definition->key, $values)) {
                continue;
            }

            $value = $definition->clamp($definition->type->cast((string) $values[$definition->key]));

            $this->settings->set(
                $definition->group,
                $definition->key,
                $definition->type->serialize($value),
                $definition->type->value,
            );
        }
    }
}
