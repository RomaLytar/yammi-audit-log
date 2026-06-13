<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Yammi\AuditLog\Application\DTO\Audit\AuditFilterData;
use Yammi\AuditLog\Application\DTO\Stats\StatsData;

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
     * The heaviest correlation chains, each linkable to its trace.
     *
     * @return list<array{id: string, short: string, writes: int, models: int, depth: int, percent: int}>
     */
    public function cascadeRows(): array
    {
        $max = 1;

        foreach ($this->stats->topCascades as $cascade) {
            $max = max($max, $cascade['writes']);
        }

        $rows = [];

        foreach ($this->stats->topCascades as $cascade) {
            $rows[] = [
                'id' => $cascade['correlation_id'],
                'short' => substr($cascade['correlation_id'], 0, 8),
                'writes' => $cascade['writes'],
                'models' => $cascade['models'],
                'depth' => $cascade['depth'],
                'percent' => (int) round($cascade['writes'] / $max * 100),
            ];
        }

        return $rows;
    }

    /**
     * Contribution-style cells: intensity 0 (no activity) to 4 (peak day).
     *
     * @return list<array{day: string, count: int, level: int}>
     */
    public function heatmapCells(): array
    {
        $max = max([1, ...array_values($this->stats->byDay)]);

        $cells = [];

        foreach ($this->stats->byDay as $day => $count) {
            $cells[] = [
                'day' => $day,
                'count' => $count,
                'level' => $count === 0 ? 0 : max(1, (int) ceil($count / $max * 4)),
            ];
        }

        return $cells;
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
