<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller\Settings;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Yammi\AuditLog\Application\Action\Settings\ResetSettingsAction;
use Yammi\AuditLog\Application\Action\Settings\UpdateSettingsAction;
use Yammi\AuditLog\Infrastructure\Http\Request\UpdateSettingsRequest;
use Yammi\AuditLog\Infrastructure\Settings\EffectiveSettingsReader;
use Yammi\AuditLog\Infrastructure\Settings\StoredSettingsApplier;
use Yammi\AuditLog\Presentation\ViewModel\GeneralSettingsViewModel;
use Yammi\AuditLog\Presentation\ViewModel\SettingsHubViewModel;

/** @internal */
final class SettingsController
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly ConfigRepository $config,
        private readonly EffectiveSettingsReader $settings,
    ) {}

    public function index(): View
    {
        $dedicated = $this->config->get('audit-log.database.connection');

        return $this->view->make('audit-log::settings', [
            'vm' => new SettingsHubViewModel(
                captureEnabled: (bool) $this->config->get('audit-log.enabled', true),
                dedicatedConnectionConfigured: is_string($dedicated) && $dedicated !== '',
            ),
        ]);
    }

    public function general(): View
    {
        return $this->view->make('audit-log::settings-general', [
            'vm' => new GeneralSettingsViewModel($this->settings->grouped()),
        ]);
    }

    public function docs(): View
    {
        return $this->view->make('audit-log::settings-docs', [
            'postmanEnabled' => (bool) $this->config->get('audit-log.api.enabled', false)
                && (bool) $this->config->get('audit-log.api.postman', true),
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
            ->route('audit-log.settings.general')
            ->with('audit_log_status', 'Settings saved.');
    }

    public function reset(ResetSettingsAction $reset): RedirectResponse
    {
        $reset();

        return redirect()
            ->route('audit-log.settings.general')
            ->with('audit_log_status', 'Settings reset to package defaults.');
    }
}
