<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Yammi\AuditLog\Application\DTO\ResolvedSettingData;
use Yammi\AuditLog\Application\DTO\VolumeMetricsData;
use Yammi\AuditLog\Infrastructure\Transfer\ConnectionStatusData;

/** @internal */
final class SettingsViewModel
{
    /**
     * @param  list<ResolvedSettingData>  $settings
     * @param  list<string>  $connectionNames
     */
    public function __construct(
        public readonly array $settings,
        public readonly ConnectionStatusData $defaultConnection,
        public readonly ?ConnectionStatusData $dedicatedConnection,
        public readonly array $connectionNames,
        public readonly VolumeMetricsData $volume,
    ) {}

    public function hasDedicatedConnection(): bool
    {
        return $this->dedicatedConnection !== null;
    }

    public function suggestedTransferTarget(): string
    {
        return $this->dedicatedConnection?->name ?? '';
    }
}
