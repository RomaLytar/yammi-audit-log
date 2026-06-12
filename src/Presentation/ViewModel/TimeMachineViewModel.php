<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Illuminate\Support\Carbon;
use Yammi\AuditLog\Application\DTO\StateData;

/**
 * Presents one reconstructed point-in-time state for the UI.
 *
 * @internal
 */
final class TimeMachineViewModel
{
    public function __construct(
        private readonly StateData $state,
        private readonly ?string $timezone = null,
    ) {}

    public function model(): string
    {
        return $this->state->model();
    }

    public function id(): string
    {
        return $this->state->auditableId;
    }

    public function existed(): bool
    {
        return $this->state->existed;
    }

    public function hasHistory(): bool
    {
        return $this->state->appliedChanges > 0;
    }

    public function appliedChanges(): int
    {
        return $this->state->appliedChanges;
    }

    public function truncated(): bool
    {
        return $this->state->truncated;
    }

    public function at(string $format = 'Y-m-d H:i'): string
    {
        return $this->present($this->state->at, $format);
    }

    public function lastChangeAt(string $format = 'Y-m-d H:i'): ?string
    {
        return $this->state->lastChangeAt === null
            ? null
            : $this->present($this->state->lastChangeAt, $format);
    }

    /**
     * @return list<array{field: string, value: string}>
     */
    public function rows(): array
    {
        $rows = [];

        foreach ($this->state->attributes as $field => $value) {
            $rows[] = [
                'field' => (string) $field,
                'value' => $value === null
                    ? '—'
                    : (is_array($value) ? (string) json_encode($value) : (string) $value),
            ];
        }

        return $rows;
    }

    private function present(string $moment, string $format): string
    {
        $parsed = Carbon::parse($moment);

        if ($this->timezone !== null && $this->timezone !== '') {
            $parsed = $parsed->setTimezone($this->timezone);
        }

        return $parsed->format($format);
    }
}
