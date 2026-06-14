<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Yammi\AuditLog\Application\DTO\Audit\AuditFilterData;
use Yammi\AuditLog\Application\DTO\Audit\ChangeListData;

/** @internal */
final class DashboardViewModel
{
    /** @var list<TimelineEntryViewModel> */
    public readonly array $entries;

    public function __construct(
        private readonly ChangeListData $list,
        ?string $jobsMonitorUrl = null,
        ?string $timezone = null,
    ) {
        $entries = [];

        foreach ($list->entries as $entry) {
            $entries[] = new TimelineEntryViewModel($entry, $list->chainSize($entry->correlationId), $jobsMonitorUrl, $timezone);
        }

        $this->entries = $entries;
    }

    public function total(): int
    {
        return $this->list->total;
    }

    public function isEmpty(): bool
    {
        return $this->list->isEmpty();
    }

    public function page(): int
    {
        return $this->list->page;
    }

    public function lastPage(): int
    {
        return $this->list->lastPage;
    }

    public function filters(): AuditFilterData
    {
        return $this->list->filters;
    }

    public function hasFilterOptions(): bool
    {
        return $this->list->models !== [];
    }

    /**
     * @return list<string>
     */
    public function models(): array
    {
        return $this->list->models;
    }

    /**
     * @return list<string>
     */
    public function actorTypes(): array
    {
        return $this->list->actorTypes;
    }

    /**
     * @return list<string>
     */
    public function events(): array
    {
        return $this->list->events;
    }
}
