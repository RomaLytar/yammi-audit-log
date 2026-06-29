<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Presentation\ViewModel;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\Audit\ChainData;
use Yammi\AuditLog\Application\DTO\Audit\ChainNodeData;
use Yammi\AuditLog\Application\DTO\Audit\TimelineEntryData;
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

        $node = new ChainNodeData(
            spanId: 'req',
            parentSpanId: null,
            entries: [$entry, $entry],
            children: [],
            depth: 0,
            actorType: 'user',
            actorLabel: 'Jane',
            originLabel: null,
            model: 'Order',
        );

        $viewModel = new TraceViewModel(new ChainData(
            correlationId: 'corr-1',
            entries: [$entry, $entry],
            modelCount: 1,
            rootActorLabel: 'Jane',
            rootModel: 'Order',
            tree: [$node],
            maxBreadth: 5,
        ));

        $this->assertSame('corr-1', $viewModel->correlationId());
        $this->assertSame(2, $viewModel->count());
        $this->assertSame(1, $viewModel->modelCount());
        $this->assertSame('Jane', $viewModel->rootActorLabel());
        $this->assertSame('Order', $viewModel->rootModel());
        $this->assertCount(2, $viewModel->entries);
        $this->assertCount(1, $viewModel->tree);
        $this->assertSame('Request', $viewModel->tree[0]->processLabel());
        $this->assertSame(2, $viewModel->tree[0]->entryCount());
        $this->assertSame(3, $viewModel->columns());
    }
}
