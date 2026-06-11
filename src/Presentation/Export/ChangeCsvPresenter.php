<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\Export;

use Yammi\AuditLog\Application\DTO\TimelineEntryData;

/** @internal */
final class ChangeCsvPresenter
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'id',
            'occurred_at',
            'model',
            'auditable_type',
            'auditable_id',
            'event',
            'actor_type',
            'actor_label',
            'origin_label',
            'correlation_id',
            'is_noise',
            'changes',
        ];
    }

    /**
     * @return list<string>
     */
    public function row(TimelineEntryData $entry): array
    {
        return array_map($this->guardFormula(...), [
            (string) $entry->id,
            $entry->occurredAt,
            $entry->model(),
            $entry->auditableType,
            $entry->auditableId,
            $entry->event,
            $entry->actorType,
            $entry->actorLabel,
            $entry->originLabel ?? '',
            $entry->correlationId ?? '',
            $entry->isNoise ? '1' : '0',
            (string) json_encode($entry->changes),
        ]);
    }

    /**
     * Audit values are attacker-controlled; a leading formula character would
     * execute when the CSV is opened in a spreadsheet.
     */
    private function guardFormula(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonRow(TimelineEntryData $entry): array
    {
        return [
            'id' => $entry->id,
            'occurred_at' => $entry->occurredAt,
            'model' => $entry->model(),
            'auditable_type' => $entry->auditableType,
            'auditable_id' => $entry->auditableId,
            'event' => $entry->event,
            'actor_type' => $entry->actorType,
            'actor_label' => $entry->actorLabel,
            'origin_label' => $entry->originLabel,
            'correlation_id' => $entry->correlationId,
            'is_noise' => $entry->isNoise,
            'changes' => $entry->changes,
            'labels' => $entry->labels,
        ];
    }
}
