<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller\Settings;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Yammi\AuditLog\Infrastructure\Transfer\ConnectionStatusInspector;
use Yammi\AuditLog\Presentation\ViewModel\DatabaseSettingsViewModel;

/** @internal */
final class DatabaseSettingsController
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly ConfigRepository $config,
        private readonly ConnectionStatusInspector $connections,
    ) {}

    public function __invoke(): View
    {
        $defaultName = $this->configString('database.default') ?? 'default';
        $dedicatedName = $this->configString('audit-log.database.connection');

        $names = $this->config->get('database.connections');

        return $this->view->make('audit-log::settings-database', [
            'vm' => new DatabaseSettingsViewModel(
                defaultConnection: $this->connections->inspect($defaultName),
                dedicatedConnection: $dedicatedName !== null && $dedicatedName !== $defaultName
                    ? $this->connections->inspect($dedicatedName)
                    : null,
                connectionNames: is_array($names) ? array_map(strval(...), array_keys($names)) : [],
            ),
        ]);
    }

    private function configString(string $key): ?string
    {
        $value = $this->config->get($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
