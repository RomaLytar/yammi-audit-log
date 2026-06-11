<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Yammi\AuditLog\Application\Action\BuildVolumeMetricsAction;
use Yammi\AuditLog\Application\Action\ResetSettingsAction;
use Yammi\AuditLog\Application\Action\UpdateSettingsAction;
use Yammi\AuditLog\Infrastructure\Http\Request\UpdateSettingsRequest;
use Yammi\AuditLog\Infrastructure\Settings\EffectiveSettingsReader;
use Yammi\AuditLog\Infrastructure\Settings\StoredSettingsApplier;
use Yammi\AuditLog\Infrastructure\Transfer\ConnectionStatusInspector;
use Yammi\AuditLog\Presentation\ViewModel\SettingsViewModel;

/** @internal */
final class SettingsController
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly ConfigRepository $config,
        private readonly EffectiveSettingsReader $settings,
        private readonly ConnectionStatusInspector $connections,
        private readonly BuildVolumeMetricsAction $volume,
    ) {}

    public function index(): View
    {
        $defaultName = $this->configString('database.default') ?? 'default';
        $dedicatedName = $this->configString('audit-log.database.connection');

        $names = $this->config->get('database.connections');
        $retentionDays = $this->config->get('audit-log.retention.days', 0);

        return $this->view->make('audit-log::settings', [
            'vm' => new SettingsViewModel(
                settings: $this->settings->all(),
                defaultConnection: $this->connections->inspect($defaultName),
                dedicatedConnection: $dedicatedName !== null && $dedicatedName !== $defaultName
                    ? $this->connections->inspect($dedicatedName)
                    : null,
                connectionNames: is_array($names) ? array_map(strval(...), array_keys($names)) : [],
                volume: ($this->volume)(is_numeric($retentionDays) ? (int) $retentionDays : 0),
            ),
        ]);
    }

    public function update(
        UpdateSettingsRequest $request,
        UpdateSettingsAction $update,
        StoredSettingsApplier $applier,
    ): RedirectResponse {
        $update($request->settings());
        $applier->apply();

        return redirect()
            ->route('audit-log.settings')
            ->with('audit_log_status', 'Settings saved.');
    }

    public function reset(ResetSettingsAction $reset): RedirectResponse
    {
        $reset();

        return redirect()
            ->route('audit-log.settings')
            ->with('audit_log_status', 'Settings reset to package defaults.');
    }

    private function configString(string $key): ?string
    {
        $value = $this->config->get($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
