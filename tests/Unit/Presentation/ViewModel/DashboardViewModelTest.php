<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Presentation\ViewModel;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\AuditFilterData;
use Yammi\AuditLog\Application\DTO\ChangeListData;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Presentation\ViewModel\DashboardViewModel;

final class DashboardViewModelTest extends TestCase
{
    public function test_it_builds_entry_view_models_with_their_chain_sizes(): void
    {
        $viewModel = new DashboardViewModel($this->list(
            entries: [$this->entry('corr-1'), $this->entry(null)],
            correlationSizes: ['corr-1' => 4],
        ));

        $this->assertCount(2, $viewModel->entries);
        $this->assertSame(4, $viewModel->entries[0]->chainSize());
        $this->assertSame(1, $viewModel->entries[1]->chainSize());
    }

    public function test_it_exposes_the_list_state(): void
    {
        $filters = new AuditFilterData(type: 'App\\Models\\Order');

        $viewModel = new DashboardViewModel($this->list(filters: $filters));

        $this->assertTrue($viewModel->isEmpty());
        $this->assertSame(0, $viewModel->total());
        $this->assertSame(2, $viewModel->page());
        $this->assertSame(5, $viewModel->lastPage());
        $this->assertSame($filters, $viewModel->filters());
        $this->assertSame(['App\\Models\\Order'], $viewModel->models());
        $this->assertSame(['user'], $viewModel->actorTypes());
        $this->assertSame(['created'], $viewModel->events());
        $this->assertTrue($viewModel->hasFilterOptions());
    }

    public function test_filter_options_are_absent_without_models(): void
    {
        $viewModel = new DashboardViewModel($this->list(models: []));

        $this->assertFalse($viewModel->hasFilterOptions());
    }

    /**
     * @param  list<TimelineEntryData>  $entries
     * @param  array<string, int>  $correlationSizes
     * @param  list<string>  $models
     */
    private function list(
        array $entries = [],
        array $correlationSizes = [],
        array $models = ['App\\Models\\Order'],
        ?AuditFilterData $filters = null,
    ): ChangeListData {
        return new ChangeListData(
            entries: $entries,
            total: 0,
            page: 2,
            perPage: 25,
            lastPage: 5,
            models: $models,
            actorTypes: ['user'],
            events: ['created'],
            filters: $filters ?? new AuditFilterData,
            correlationSizes: $correlationSizes,
        );
    }

    private function entry(?string $correlationId): TimelineEntryData
    {
        return new TimelineEntryData(
            id: 1,
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: 'created',
            actorType: 'user',
            actorLabel: 'Jane',
            originLabel: null,
            changes: [],
            labels: [],
            occurredAt: '2026-01-01T10:00:00+00:00',
            correlationId: $correlationId,
        );
    }
}
