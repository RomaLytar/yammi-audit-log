<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Yammi\AuditLog\Application\DTO\AuditFilterData;
use Yammi\AuditLog\Application\DTO\StatsData;

/** @internal */
final class StatsViewModel
{
    public function __construct(
        private readonly StatsData $stats,
    ) {}

    public function total(): int
    {
        return $this->stats->total;
    }

    public function last30Days(): int
    {
        return $this->stats->last30Days;
    }

    public function perDay(): float
    {
        return $this->stats->perDay;
    }

    public function projectedRows(): ?int
    {
        return $this->stats->projectedRows;
    }

    public function filters(): AuditFilterData
    {
        return $this->stats->filters;
    }

    /**
     * @return list<string>
     */
    public function models(): array
    {
        return $this->stats->models;
    }

    /**
     * @return list<string>
     */
    public function actorTypes(): array
    {
        return $this->stats->actorTypes;
    }

    /**
     * @return list<string>
     */
    public function events(): array
    {
        return $this->stats->events;
    }

    /**
     * @return list<array{label: string, count: int, percent: int}>
     */
    public function eventRows(): array
    {
        return $this->rows($this->stats->byEvent);
    }

    /**
     * @return list<array{label: string, count: int, percent: int}>
     */
    public function actorTypeRows(): array
    {
        return $this->rows($this->stats->byActorType);
    }

    /**
     * @return list<array{label: string, count: int, percent: int}>
     */
    public function modelRows(): array
    {
        $rows = [];

        foreach ($this->rows($this->stats->byModel) as $row) {
            $parts = explode('\\', $row['label']);
            $row['label'] = (end($parts) ?: $row['label']);
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return list<array{day: string, count: int, percent: int}>
     */
    public function dailyBars(): array
    {
        $max = max([1, ...array_values($this->stats->byDay)]);

        $bars = [];

        foreach ($this->stats->byDay as $day => $count) {
            $bars[] = [
                'day' => $day,
                'count' => $count,
                'percent' => (int) round($count / $max * 100),
            ];
        }

        return $bars;
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array{label: string, count: int, percent: int}>
     */
    private function rows(array $counts): array
    {
        $max = max([1, ...array_values($counts)]);

        $rows = [];

        foreach ($counts as $label => $count) {
            $rows[] = [
                'label' => $label,
                'count' => $count,
                'percent' => (int) round($count / $max * 100),
            ];
        }

        return $rows;
    }
}
