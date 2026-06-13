<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO\Audit;

final class RecordViewData
{
    /**
     * @param  list<TimelineEntryData>  $entries
     * @param  list<RelatedChangeData>  $related
     */
    public function __construct(
        public readonly string $auditableType,
        public readonly string $auditableId,
        public readonly string $referenceField,
        public readonly array $entries,
        public readonly array $related,
    ) {}

    public function model(): string
    {
        $parts = explode('\\', $this->auditableType);

        return end($parts) ?: $this->auditableType;
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }
}
