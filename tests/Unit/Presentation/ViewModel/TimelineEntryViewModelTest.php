<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Presentation\ViewModel;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Presentation\ViewModel\TimelineEntryViewModel;

final class TimelineEntryViewModelTest extends TestCase
{
    public function test_it_presents_the_entry_fields(): void
    {
        $entry = $this->viewModel(chainSize: 3);

        $this->assertSame('Order', $entry->model());
        $this->assertSame('7', $entry->id());
        $this->assertSame('updated', $entry->event());
        $this->assertSame('user', $entry->actorType());
        $this->assertSame('Jane', $entry->actorLabel());
        $this->assertSame('app:sync', $entry->originLabel());
        $this->assertSame('corr-1', $entry->correlationId());
        $this->assertTrue($entry->isNoise());
        $this->assertSame(3, $entry->chainSize());
        $this->assertTrue($entry->hasChain());
        $this->assertSame(2, $entry->changeCount());
    }

    public function test_a_single_change_has_no_chain(): void
    {
        $this->assertFalse($this->viewModel(chainSize: 1)->hasChain());
    }

    public function test_occurred_at_is_formatted(): void
    {
        $entry = $this->viewModel(chainSize: 1);

        $this->assertSame('2026-01-01 10:00', $entry->occurredAt());
        $this->assertSame('01.01.2026', $entry->occurredAt('d.m.Y'));
    }

    public function test_change_rows_present_nulls_and_arrays_as_strings(): void
    {
        $rows = $this->viewModel(chainSize: 1)->changes();

        $this->assertSame(
            [
                ['field' => 'status', 'old' => 'a', 'new' => 'b'],
                ['field' => 'meta', 'old' => '—', 'new' => '{"x":1}'],
            ],
            $rows,
        );
    }

    private function viewModel(int $chainSize): TimelineEntryViewModel
    {
        return new TimelineEntryViewModel(new TimelineEntryData(
            id: 12,
            auditableType: 'App\\Models\\Order',
            auditableId: '7',
            event: 'updated',
            actorType: 'user',
            actorLabel: 'Jane',
            originLabel: 'app:sync',
            changes: [
                'status' => ['old' => 'a', 'new' => 'b'],
                'meta' => ['old' => null, 'new' => ['x' => 1]],
            ],
            labels: [],
            occurredAt: '2026-01-01T10:00:00+00:00',
            correlationId: 'corr-1',
            isNoise: true,
        ), $chainSize);
    }
}
