<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Yammi\AuditLog\Application\DTO\ResolvedSettingData;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Infrastructure\Transfer\ConnectionStatusData;

/** @internal */
final class SettingsViewModel
{
    private const GROUP_TITLES = [
        SettingRegistry::GROUP_GENERAL => ['General', 'database-zap'],
        SettingRegistry::GROUP_WRITE => ['Writing', 'send'],
        SettingRegistry::GROUP_CAPTURE => ['Capture', 'radar'],
        SettingRegistry::GROUP_REDACTION => ['Redaction', 'eye-off'],
        SettingRegistry::GROUP_UI => ['Dashboard', 'layout-dashboard'],
    ];

    /**
     * @param  array<string, list<ResolvedSettingData>>  $settings
     * @param  list<string>  $connectionNames
     */
    public function __construct(
        public readonly array $settings,
        public readonly ConnectionStatusData $defaultConnection,
        public readonly ?ConnectionStatusData $dedicatedConnection,
        public readonly array $connectionNames,
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

    public function hasDedicatedConnection(): bool
    {
        return $this->dedicatedConnection !== null;
    }

    public function suggestedTransferTarget(): string
    {
        return $this->dedicatedConnection?->name ?? '';
    }
}
