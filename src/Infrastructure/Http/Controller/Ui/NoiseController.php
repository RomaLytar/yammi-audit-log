<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller\Ui;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Yammi\AuditLog\Application\Action\Read\ListChangesAction;
use Yammi\AuditLog\Infrastructure\Http\FilterFactory;
use Yammi\AuditLog\Infrastructure\Integration\JobsMonitorBridge;
use Yammi\AuditLog\Infrastructure\Support\AuditTimezone;
use Yammi\AuditLog\Presentation\ViewModel\DashboardViewModel;

/** @internal */
final class NoiseController
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly ListChangesAction $listChanges,
        private readonly FilterFactory $filters,
        private readonly JobsMonitorBridge $jobsMonitor,
        private readonly AuditTimezone $timezone,
    ) {}

    public function __invoke(Request $request): View
    {
        $list = ($this->listChanges)($this->filters->fromRequest($request), onlyNoise: true);

        return $this->view->make('audit-log::noise', [
            'list' => new DashboardViewModel($list, $this->jobsMonitor->url(), $this->timezone->name()),
        ]);
    }
}
