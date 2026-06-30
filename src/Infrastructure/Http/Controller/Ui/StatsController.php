<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller\Ui;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Yammi\AuditLog\Application\Action\Read\BuildStatsAction;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Infrastructure\Capture\CaptureFailureLog;
use Yammi\AuditLog\Infrastructure\Http\FilterFactory;
use Yammi\AuditLog\Presentation\ViewModel\StatsViewModel;

/** @internal */
final class StatsController
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly BuildStatsAction $buildStats,
        private readonly FilterFactory $filters,
        private readonly ConfigRepository $config,
        private readonly CaptureFailureLog $captureFailures,
        private readonly Clock $clock,
    ) {}

    public function __invoke(Request $request): View
    {
        $retentionDays = $this->config->get('audit-log.retention.days', 0);

        $stats = ($this->buildStats)(
            $this->filters->fromRequest($request),
            is_numeric($retentionDays) ? (int) $retentionDays : 0,
        );

        $health = $this->captureFailures->health($this->clock->now()->modify('-24 hours'));

        return $this->view->make('audit-log::stats', [
            'stats' => new StatsViewModel($stats),
            'captureFailureCount' => $health['count'],
            'captureFailures' => $health['recent'],
        ]);
    }
}
