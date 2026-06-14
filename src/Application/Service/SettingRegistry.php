<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service;

use Yammi\AuditLog\Application\DTO\Settings\SettingDefinitionData;
use Yammi\AuditLog\Application\Service\Definition\AlertSettings;
use Yammi\AuditLog\Application\Service\Definition\AnomalySettings;
use Yammi\AuditLog\Application\Service\Definition\CaptureSettings;
use Yammi\AuditLog\Application\Service\Definition\GeneralSettings;
use Yammi\AuditLog\Application\Service\Definition\RedactionSettings;
use Yammi\AuditLog\Application\Service\Definition\SettingGroupProvider;
use Yammi\AuditLog\Application\Service\Definition\UiSettings;
use Yammi\AuditLog\Application\Service\Definition\WriteSettings;

/**
 * The catalogue of operator-editable settings: where each one lives in config,
 * its type, bounds and UI copy. Stored values overlay config at boot, so the
 * precedence is: saved setting > published config/env > package default.
 * Bootstrap-critical values (database connection, route path, middleware,
 * gate) stay config-only on purpose — a typo there can lock the dashboard out.
 *
 * The definitions themselves live in one SettingGroupProvider per group; this
 * registry concatenates them and offers the lookups the rest of the app uses.
 *
 * @internal
 */
final class SettingRegistry
{
    public const GROUP_GENERAL = 'general';

    public const GROUP_WRITE = 'write';

    public const GROUP_CAPTURE = 'capture';

    public const GROUP_REDACTION = 'redaction';

    public const GROUP_ALERTS = 'alerts';

    public const GROUP_ANOMALIES = 'anomalies';

    public const GROUP_UI = 'ui';

    /**
     * @return list<SettingDefinitionData>
     */
    public function all(): array
    {
        $definitions = [];

        foreach ($this->groups() as $group) {
            foreach ($group->definitions() as $definition) {
                $definitions[] = $definition;
            }
        }

        return $definitions;
    }

    /**
     * @return array<string, list<SettingDefinitionData>>
     */
    public function grouped(): array
    {
        $grouped = [];

        foreach ($this->all() as $definition) {
            $grouped[$definition->group][] = $definition;
        }

        return $grouped;
    }

    public function find(string $key): ?SettingDefinitionData
    {
        foreach ($this->all() as $definition) {
            if ($definition->key === $key) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @return list<SettingGroupProvider>
     */
    private function groups(): array
    {
        return [
            new GeneralSettings,
            new WriteSettings,
            new CaptureSettings,
            new RedactionSettings,
            new AlertSettings,
            new AnomalySettings,
            new UiSettings,
        ];
    }
}
