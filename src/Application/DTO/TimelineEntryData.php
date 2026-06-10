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
     */
    public function __construct(
        public readonly ?int $id,
        public readonly string $event,
        public readonly string $actorType,
        public readonly string $actorLabel,
        public readonly ?string $originLabel,
        public readonly array $changes,
        public readonly array $labels,
        public readonly string $occurredAt,
    ) {}

    public static function fromRecord(AuditRecord $record): self
    {
        return new self(
            id: $record->id(),
            event: $record->event()->value,
            actorType: $record->actor()->type->value,
            actorLabel: $record->actor()->displayLabel(),
            originLabel: $record->origin()?->displayLabel(),
            changes: $record->diff()->toArray(),
            labels: $record->labels()->all(),
            occurredAt: $record->occurredAt()->format(DateTimeInterface::ATOM),
        );
    }
}
