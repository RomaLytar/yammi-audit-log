<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Yammi\AuditLog\Infrastructure\Transfer\ConnectionStatusData;

/** @internal */
final class DatabaseSettingsViewModel
{
    /**
     * @param  list<string>  $connectionNames
     */
    public function __construct(
        public readonly ConnectionStatusData $defaultConnection,
        public readonly ?ConnectionStatusData $dedicatedConnection,
        public readonly array $connectionNames,
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
