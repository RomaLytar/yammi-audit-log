<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Yammi\AuditLog\Infrastructure\Anomaly\AnomalyScanner;
use Yammi\AuditLog\Presentation\ViewModel\AnomaliesViewModel;

/** @internal */
final class AnomaliesController
{
    private const WINDOWS = [
        60 => 'Last hour',
        360 => 'Last 6 hours',
        1440 => 'Last 24 hours',
        10080 => 'Last 7 days',
        43200 => 'Last 30 days',
    ];

    private const DEFAULT_WINDOW = 1440;

    public function __construct(
        private readonly ViewFactory $view,
        private readonly AnomalyScanner $scanner,
        private readonly ConfigRepository $config,
    ) {}

    public function __invoke(Request $request): View
    {
        $validated = $request->validate(['window' => 'sometimes|nullable|integer']);

        $window = (int) ($validated['window'] ?? self::DEFAULT_WINDOW);

        if (! array_key_exists($window, self::WINDOWS)) {
            $window = self::DEFAULT_WINDOW;
        }

        return $this->view->make('audit-log::anomalies', [
            'model' => new AnomaliesViewModel(
                $this->scanner->scan($window),
                $window,
                self::WINDOWS,
                $this->ruleSummaries(),
                $this->scanCron(),
            ),
        ]);
    }

    /**
     * @return list<string>
     */
    private function ruleSummaries(): array
    {
        $rate = (int) $this->config->get('audit-log.anomalies.rate_threshold', 200);
        $delete = (int) $this->config->get('audit-log.anomalies.delete_threshold', 25);
        $offHours = $this->config->get('audit-log.anomalies.off_hours', []);

        $summaries = [
            $rate > 0 ? "Change burst: more than {$rate} changes by one actor" : 'Change burst: off',
            $delete > 0 ? "Mass delete: more than {$delete} deletions by one actor" : 'Mass delete: off',
        ];

        if (is_array($offHours) && count($offHours) === 2
            && is_numeric($offHours[array_key_first($offHours)] ?? null)) {
            $hours = array_values($offHours);
            $summaries[] = sprintf('Off-hours: user activity between %02d:00 and %02d:59', (int) $hours[0], (int) $hours[1]);
        } else {
            $summaries[] = 'Off-hours: off';
        }

        return $summaries;
    }

    private function scanCron(): ?string
    {
        $cron = $this->config->get('audit-log.anomalies.cron');

        return is_string($cron) && trim($cron) !== '' ? trim($cron) : null;
    }
}
