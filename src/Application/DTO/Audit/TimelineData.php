<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO\Audit;

final class TimelineData
{
    /**
     * @param  list<TimelineEntryData>  $entries
     */
    public function __construct(
        public readonly string $auditableType,
        public readonly string $auditableId,
        public readonly array $entries,
    ) {}

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    public function count(): int
    {
        return count($this->entries);
    }
}
