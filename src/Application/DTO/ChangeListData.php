<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO;

/** @internal */
final class ChangeListData
{
    /**
     * @param  list<TimelineEntryData>  $entries
     * @param  list<string>  $models
     * @param  list<string>  $actorTypes
     * @param  list<string>  $events
     * @param  array<string, int>  $correlationSizes
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
        public readonly array $correlationSizes = [],
    ) {}

    /**
     * How many changes belong to this entry's chain (1 when it stands alone).
     */
    public function chainSize(?string $correlationId): int
    {
        if ($correlationId === null) {
            return 1;
        }

        return $this->correlationSizes[$correlationId] ?? 1;
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }
}
