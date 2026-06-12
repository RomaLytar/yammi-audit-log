<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Illuminate\Support\Carbon;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;

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

    public function id(): string
    {
        return $this->entry->auditableId;
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
        $moment = Carbon::parse($this->entry->occurredAt);

        if ($this->timezone !== null && $this->timezone !== '') {
            $moment = $moment->setTimezone($this->timezone);
        }

        return $moment->format($format);
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

        foreach ($this->entry->changes as $field => $pair) {
            $rows[] = [
                'field' => (string) $field,
                'old' => $this->present($pair['old'] ?? null),
                'new' => $this->present($pair['new'] ?? null),
                'oldLabel' => $this->entry->labels[$field.'.old'] ?? null,
                'newLabel' => $this->entry->labels[$field.'.new'] ?? null,
            ];
        }

        return $rows;
    }

    private function present(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        return is_array($value) ? (string) json_encode($value) : (string) $value;
    }
}
