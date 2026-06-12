<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

/** @internal */
final class SettingsHubViewModel
{
    public function __construct(
        private readonly bool $captureEnabled,
        private readonly bool $dedicatedConnectionConfigured,
    ) {}

    /**
     * @return list<array{name: string, description: string, enabled: bool, route: string}>
     */
    public function blocks(): array
    {
        return [
            [
                'name' => 'General Settings',
                'description' => 'Capture toggle, retention and prune schedule, async writes, ignored attributes, redaction, dashboard limits.',
                'enabled' => $this->captureEnabled,
                'route' => 'audit-log.settings.general',
            ],
            [
                'name' => 'Database Connection',
                'description' => 'Store audit data on a dedicated connection and transfer existing records between databases.',
                'enabled' => $this->dedicatedConnectionConfigured,
                'route' => 'audit-log.settings.database',
            ],
            [
                'name' => 'Documentation',
                'description' => 'What gets recorded, how every feature works and how to use it — capture, attribution, integrity, alerts, export, API.',
                'enabled' => true,
                'route' => 'audit-log.settings.docs',
            ],
            [
                'name' => 'Facade Playground',
                'description' => 'Every public facade method with a real example — browse the catalog, run a call, see the JSON result.',
                'enabled' => true,
                'route' => 'audit-log.playground',
            ],
        ];
    }
}
