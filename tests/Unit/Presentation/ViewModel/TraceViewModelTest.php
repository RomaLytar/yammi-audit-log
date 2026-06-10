<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Presentation\ViewModel;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\ChainData;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Presentation\ViewModel\TraceViewModel;

final class TraceViewModelTest extends TestCase
{
    public function test_it_presents_the_chain(): void
    {
        $entry = new TimelineEntryData(
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
            correlationId: 'corr-1',
        );

        $viewModel = new TraceViewModel(new ChainData(
            correlationId: 'corr-1',
            entries: [$entry, $entry],
            modelCount: 1,
            rootActorLabel: 'Jane',
            rootModel: 'Order',
        ));

        $this->assertSame('corr-1', $viewModel->correlationId());
        $this->assertSame(2, $viewModel->count());
        $this->assertSame(1, $viewModel->modelCount());
        $this->assertSame('Jane', $viewModel->rootActorLabel());
        $this->assertSame('Order', $viewModel->rootModel());
        $this->assertCount(2, $viewModel->entries);
    }
}
