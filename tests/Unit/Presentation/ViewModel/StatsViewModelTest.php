<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Presentation\ViewModel;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\AuditFilterData;
use Yammi\AuditLog\Application\DTO\StatsData;
use Yammi\AuditLog\Presentation\ViewModel\StatsViewModel;

final class StatsViewModelTest extends TestCase
{
    public function test_heatmap_levels_scale_with_the_peak_day(): void
    {
        $viewModel = new StatsViewModel($this->stats(byDay: [
            '2026-06-01' => 0,
            '2026-06-02' => 1,
            '2026-06-03' => 4,
            '2026-06-04' => 8,
        ]));

        $cells = $viewModel->heatmapCells();

        $this->assertSame(['2026-06-01', 0, 0], [$cells[0]['day'], $cells[0]['count'], $cells[0]['level']]);
        $this->assertSame(1, $cells[1]['level']);
        $this->assertSame(2, $cells[2]['level']);
        $this->assertSame(4, $cells[3]['level']);
    }

    public function test_breakdown_rows_carry_relative_percentages(): void
    {
        $viewModel = new StatsViewModel($this->stats(byEvent: ['updated' => 10, 'created' => 5]));

        $rows = $viewModel->eventRows();

        $this->assertSame(['updated', 10, 100], [$rows[0]['label'], $rows[0]['count'], $rows[0]['percent']]);
        $this->assertSame(50, $rows[1]['percent']);
    }

    public function test_model_rows_use_class_basenames(): void
    {
        $viewModel = new StatsViewModel($this->stats(byModel: ['App\\Models\\Order' => 3]));

        $this->assertSame('Order', $viewModel->modelRows()[0]['label']);
    }

    /**
     * @param  array<string, int>  $byEvent
     * @param  array<string, int>  $byModel
     * @param  array<string, int>  $byDay
     */
    private function stats(array $byEvent = [], array $byModel = [], array $byDay = []): StatsData
    {
        return new StatsData(
            total: 0,
            last30Days: 0,
            perDay: 0.0,
            projectedRows: null,
            byEvent: $byEvent,
            byActorType: [],
            byModel: $byModel,
            byDay: $byDay,
            filters: new AuditFilterData,
        );
    }
}
