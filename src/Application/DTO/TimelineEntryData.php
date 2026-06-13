<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO;

use DateTimeInterface;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;

final class TimelineEntryData
{
    /**
     * @param  array<string, array{old: scalar|array<array-key, mixed>|null, new: scalar|array<array-key, mixed>|null}>  $changes
     * @param  array<string, string>  $labels
     * @param  array<string, string>  $context
     */
    public function __construct(
        public readonly ?int $id,
        public readonly string $auditableType,
        public readonly string $auditableId,
        public readonly string $event,
        public readonly string $actorType,
        public readonly string $actorLabel,
        public readonly ?string $originLabel,
        public readonly array $changes,
        public readonly array $labels,
        public readonly string $occurredAt,
        public readonly ?string $correlationId,
        public readonly bool $isNoise = false,
        public readonly array $context = [],
        public readonly int $chainDepth = 0,
        public readonly ?string $reason = null,
    ) {}

    public static function fromRecord(AuditRecord $record): self
    {
        return new self(
            id: $record->id(),
            auditableType: $record->auditable()->type,
            auditableId: $record->auditable()->id,
            event: $record->event()->value,
            actorType: $record->actor()->type->value,
            actorLabel: $record->actor()->displayLabel(),
            originLabel: $record->origin()?->displayLabel(),
            changes: $record->diff()->toArray(),
            labels: $record->labels()->all(),
            occurredAt: $record->occurredAt()->format(DateTimeInterface::ATOM),
            correlationId: $record->correlationId(),
            isNoise: $record->isNoise(),
            context: $record->context(),
            chainDepth: $record->chainDepth(),
            reason: $record->reason(),
        );
    }

    public function model(): string
    {
        $parts = explode('\\', $this->auditableType);

        return end($parts) ?: $this->auditableType;
    }
}
