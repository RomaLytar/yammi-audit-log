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
            traceId: '4bf92f3577b34da6a3ce929d0e0e4736',
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
        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $viewModel->traceId());
        $this->assertNull($viewModel->traceUrl());
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

    public function test_it_deep_links_the_trace_when_a_backend_url_is_configured(): void
    {
        $viewModel = new TraceViewModel(
            $this->chainWithTrace('4bf92f3577b34da6a3ce929d0e0e4736'),
            null,
            null,
            'https://apm.example.com/trace/{trace_id}',
        );

        $this->assertSame(
            'https://apm.example.com/trace/4bf92f3577b34da6a3ce929d0e0e4736',
            $viewModel->traceUrl(),
        );
    }

    public function test_there_is_no_trace_link_without_a_captured_trace(): void
    {
        $viewModel = new TraceViewModel(
            $this->chainWithTrace(null),
            null,
            null,
            'https://apm.example.com/trace/{trace_id}',
        );

        $this->assertNull($viewModel->traceUrl());
    }

    private function chainWithTrace(?string $traceId): ChainData
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
            traceId: $traceId,
        );

        return new ChainData(
            correlationId: 'corr-1',
            entries: [$entry],
            modelCount: 1,
            rootActorLabel: 'Jane',
            rootModel: 'Order',
            tree: [],
            maxBreadth: 1,
        );
    }
}
