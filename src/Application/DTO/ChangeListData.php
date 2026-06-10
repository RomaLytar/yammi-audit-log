<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO;

final class ChangeListData
{
    /**
     * @param  list<TimelineEntryData>  $entries
     * @param  list<string>  $models
     * @param  list<string>  $actorTypes
     * @param  list<string>  $events
     */
    public function __construct(
        public readonly array $entries,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $lastPage,
        public readonly array $models,
        public readonly array $actorTypes,
        public readonly array $events,
        public readonly AuditFilterData $filters,
    ) {}

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }
}
