<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Yammi\AuditLog\Application\DTO\Settings\ResolvedSettingData;
use Yammi\AuditLog\Application\Service\SettingRegistry;

/** @internal */
final class GeneralSettingsViewModel
{
    private const GROUP_TITLES = [
        SettingRegistry::GROUP_GENERAL => ['General', 'database-zap'],
        SettingRegistry::GROUP_WRITE => ['Writing', 'send'],
        SettingRegistry::GROUP_CAPTURE => ['Capture', 'radar'],
        SettingRegistry::GROUP_REDACTION => ['Redaction', 'eye-off'],
        SettingRegistry::GROUP_ALERTS => ['Alerts', 'bell-ring'],
        SettingRegistry::GROUP_ANOMALIES => ['Anomaly detection', 'siren'],
        SettingRegistry::GROUP_UI => ['Dashboard', 'layout-dashboard'],
    ];

    /**
     * @param  array<string, list<ResolvedSettingData>>  $settings
     */
    public function __construct(
        public readonly array $settings,
    ) {}

    /**
     * @return list<array{title: string, icon: string, settings: list<ResolvedSettingData>}>
     */
    public function sections(): array
    {
        $sections = [];

        foreach ($this->settings as $group => $settings) {
            [$title, $icon] = self::GROUP_TITLES[$group] ?? [ucfirst($group), 'settings'];

            $sections[] = ['title' => $title, 'icon' => $icon, 'settings' => $settings];
        }

        return $sections;
    }
}
