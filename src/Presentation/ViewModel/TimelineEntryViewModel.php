<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Presentation\ViewModel\Support\MomentFormatter;
use Yammi\AuditLog\Presentation\ViewModel\Support\ValuePresenter;

/**
 * Presents one change for the UI: pre-formatted strings, dates and diff rows, so
 * the Blade templates stay dumb markup.
 *
 * @internal
 */
final class TimelineEntryViewModel
{
    public function __construct(
        private readonly TimelineEntryData $entry,
        private readonly int $chainSize,
        private readonly ?string $jobsMonitorUrl = null,
        private readonly ?string $timezone = null,
    ) {}

    public function jobsMonitorLink(): ?string
    {
        if ($this->jobsMonitorUrl === null || $this->entry->actorType !== 'job') {
            return null;
        }

        return rtrim($this->jobsMonitorUrl, '/').'?search='.rawurlencode($this->entry->actorLabel);
    }

    public function model(): string
    {
        return $this->entry->model();
    }

    public function auditableType(): string
    {
        return $this->entry->auditableType;
    }

    public function id(): string
    {
        return $this->entry->auditableId;
    }

    public function recordId(): ?int
    {
        return $this->entry->id;
    }

    /**
     * @return list<string>
     */
    public function changedFieldNames(): array
    {
        return array_map(strval(...), array_keys($this->entry->changes));
    }

    public function event(): string
    {
        return $this->entry->event;
    }

    public function actorType(): string
    {
        return $this->entry->actorType;
    }

    public function actorLabel(): string
    {
        return $this->entry->actorLabel;
    }

    public function originLabel(): ?string
    {
        return $this->entry->originLabel;
    }

    public function isNoise(): bool
    {
        return $this->entry->isNoise;
    }

    public function reason(): ?string
    {
        return $this->entry->reason;
    }

    public function chainDepth(): int
    {
        return min(6, $this->entry->chainDepth);
    }

    /**
     * @return array<string, string>
     */
    public function requestContext(): array
    {
        return $this->entry->context;
    }

    public function correlationId(): ?string
    {
        return $this->entry->correlationId;
    }

    public function chainSize(): int
    {
        return $this->chainSize;
    }

    public function hasChain(): bool
    {
        return $this->chainSize > 1;
    }

    public function occurredAt(string $format = 'Y-m-d H:i'): string
    {
        return (new MomentFormatter($this->timezone))->format($this->entry->occurredAt, $format);
    }

    public function changeCount(): int
    {
        return count($this->entry->changes);
    }

    /**
     * @return list<array{field: string, old: string, new: string, oldLabel: ?string, newLabel: ?string}>
     */
    public function changes(): array
    {
        $rows = [];
        $values = new ValuePresenter;

        foreach ($this->entry->changes as $field => $pair) {
            $rows[] = [
                'field' => (string) $field,
                'old' => $values->present($pair['old'] ?? null),
                'new' => $values->present($pair['new'] ?? null),
                'oldLabel' => $this->entry->labels[$field.'.old'] ?? null,
                'newLabel' => $this->entry->labels[$field.'.new'] ?? null,
            ];
        }

        return $rows;
    }
}
