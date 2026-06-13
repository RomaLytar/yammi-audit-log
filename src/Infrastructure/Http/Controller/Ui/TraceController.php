<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller\Ui;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Yammi\AuditLog\Application\Action\Read\BuildChainAction;
use Yammi\AuditLog\Infrastructure\Integration\JobsMonitorBridge;
use Yammi\AuditLog\Infrastructure\Support\AuditTimezone;
use Yammi\AuditLog\Presentation\ViewModel\TraceViewModel;

/** @internal */
final class TraceController
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly BuildChainAction $buildChain,
        private readonly JobsMonitorBridge $jobsMonitor,
        private readonly AuditTimezone $timezone,
    ) {}

    public function __invoke(Request $request, string $correlation): View
    {
        $chain = ($this->buildChain)($correlation);

        if ($chain === null) {
            abort(404);
        }

        $entry = $request->query('entry');

        return $this->view->make('audit-log::trace', [
            'chain' => new TraceViewModel($chain, $this->jobsMonitor->url(), $this->timezone->name()),
            'focus' => is_numeric($entry) ? (int) $entry : null,
        ]);
    }
}
