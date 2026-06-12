<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO;

final class SubjectReportData
{
    /**
     * @param  list<TimelineEntryData>  $recordChanges
     * @param  list<TimelineEntryData>  $actorChanges
     */
    public function __construct(
        public readonly string $auditableType,
        public readonly string $auditableId,
        public readonly string $generatedAt,
        public readonly array $recordChanges,
        public readonly array $actorChanges,
        public readonly bool $truncated = false,
    ) {}

    public function model(): string
    {
        $parts = explode('\\', $this->auditableType);

        return end($parts) ?: $this->auditableType;
    }

    public function isEmpty(): bool
    {
        return $this->recordChanges === [] && $this->actorChanges === [];
    }
}
