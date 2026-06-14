<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller\Ui;

use DateInterval;
use DateTimeImmutable;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Yammi\AuditLog\Application\Contract\Query\AuditLogQuery;
use Yammi\AuditLog\Application\DTO\Audit\TimelineEntryData;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Infrastructure\AuditLogManager;
use Yammi\AuditLog\Infrastructure\Support\AuditTimezone;
use Yammi\AuditLog\Presentation\ViewModel\TimelineEntryViewModel;
use Yammi\AuditLog\Presentation\ViewModel\TimeMachineViewModel;

/** @internal */
final class TimeMachineController
{
    private const HISTORY_DISPLAY_LIMIT = 100;

    public function __construct(
        private readonly ViewFactory $view,
        private readonly AuditLogManager $manager,
        private readonly AuditLogQuery $query,
        private readonly AuditTimezone $timezone,
    ) {}

    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'type' => 'sometimes|nullable|string|max:191',
            'id' => 'sometimes|nullable|string|max:64',
            'at' => 'sometimes|nullable|date',
        ]);

        $type = trim((string) ($validated['type'] ?? ''));
        $id = trim((string) ($validated['id'] ?? ''));
        $at = trim((string) ($validated['at'] ?? ''));

        $state = null;
        $history = [];
        $rangeFrom = null;
        $rangeTo = null;

        if ($type !== '' && $id !== '') {
            $stateData = $this->manager->stateAt($type, $id, $at === '' ? null : $at);
            $state = new TimeMachineViewModel($stateData, $this->timezone->name());

            $moment = new DateTimeImmutable($stateData->at);
            $rangeTo = $moment->format('Y-m-d');
            $rangeFrom = $moment->sub(new DateInterval('P364D'))->format('Y-m-d');

            $history = $this->history($type, $id, $moment);
        }

        return $this->view->make('audit-log::time-machine', [
            'models' => $this->query->distinctModels(),
            'state' => $state,
            'type' => $type,
            'id' => $id,
            'at' => $at,
            'history' => $history,
            'rangeFrom' => $rangeFrom,
            'rangeTo' => $rangeTo,
        ]);
    }

    /**
     * @return list<TimelineEntryViewModel>
     */
    private function history(string $type, string $id, DateTimeImmutable $until): array
    {
        $entries = [];

        $records = $this->query->historyFor(
            AuditableReference::to($type, $id),
            $until,
            self::HISTORY_DISPLAY_LIMIT,
        );

        foreach ($records as $record) {
            $entries[] = new TimelineEntryViewModel(
                TimelineEntryData::fromRecord($record),
                0,
                null,
                $this->timezone->name(),
            );
        }

        return $entries;
    }
}
