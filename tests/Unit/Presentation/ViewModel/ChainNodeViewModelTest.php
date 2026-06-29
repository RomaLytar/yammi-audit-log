<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Presentation\ViewModel;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\Audit\ChainNodeData;
use Yammi\AuditLog\Application\DTO\Audit\TimelineEntryData;
use Yammi\AuditLog\Presentation\ViewModel\ChainNodeViewModel;

final class ChainNodeViewModelTest extends TestCase
{
    public function test_it_presents_a_span_node_and_its_nested_children(): void
    {
        $child = new ChainNodeData(
            spanId: 'job-a',
            parentSpanId: 'req',
            entries: [$this->entry('job', 'PublishPostJob')],
            children: [],
            depth: 1,
            actorType: 'job',
            actorLabel: 'PublishPostJob',
            originLabel: 'Jane',
            model: 'Payment',
        );

        $root = new ChainNodeData(
            spanId: 'req',
            parentSpanId: null,
            entries: [$this->entry('user', 'Jane')],
            children: [$child],
            depth: 0,
            actorType: 'user',
            actorLabel: 'Jane',
            originLabel: null,
            model: 'Order',
        );

        $viewModel = new ChainNodeViewModel($root);

        $this->assertTrue($viewModel->isRoot());
        $this->assertSame(0, $viewModel->depth());
        $this->assertSame('Request', $viewModel->processLabel());
        $this->assertSame('globe', $viewModel->processIcon());
        $this->assertSame('Order', $viewModel->model());
        $this->assertSame('user', $viewModel->actorType());
        $this->assertSame('Jane', $viewModel->actorLabel());
        $this->assertNull($viewModel->originLabel());
        $this->assertSame(1, $viewModel->entryCount());
        $this->assertTrue($viewModel->hasChildren());
        $this->assertCount(1, $viewModel->children);

        $childViewModel = $viewModel->children[0];
        $this->assertFalse($childViewModel->isRoot());
        $this->assertSame('Queued job', $childViewModel->processLabel());
        $this->assertSame('layers', $childViewModel->processIcon());
        $this->assertSame('Jane', $childViewModel->originLabel());
        $this->assertFalse($childViewModel->hasChildren());
    }

    public function test_process_labels_and_icons_cover_each_actor_type(): void
    {
        $cases = [
            'command' => ['Console command', 'terminal'],
            'scheduler' => ['Scheduled task', 'clock'],
            'system' => ['System', 'cpu'],
            'something-else' => ['Process', 'cpu'],
        ];

        foreach ($cases as $type => [$label, $icon]) {
            $viewModel = new ChainNodeViewModel(new ChainNodeData(
                spanId: 'span',
                parentSpanId: null,
                entries: [],
                children: [],
                depth: 0,
                actorType: $type,
                actorLabel: 'x',
                originLabel: null,
                model: 'Order',
            ));

            $this->assertSame($label, $viewModel->processLabel(), $type);
            $this->assertSame($icon, $viewModel->processIcon(), $type);
        }
    }

    public function test_depth_is_capped_for_indentation(): void
    {
        $viewModel = new ChainNodeViewModel(new ChainNodeData(
            spanId: 'span',
            parentSpanId: 'parent',
            entries: [],
            children: [],
            depth: 50,
            actorType: 'job',
            actorLabel: 'x',
            originLabel: null,
            model: 'Order',
        ));

        $this->assertSame(8, $viewModel->depth());
        $this->assertFalse($viewModel->isRoot());
    }

    private function entry(string $actorType, string $actorLabel): TimelineEntryData
    {
        return new TimelineEntryData(
            id: 1,
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: 'created',
            actorType: $actorType,
            actorLabel: $actorLabel,
            originLabel: null,
            changes: [],
            labels: [],
            occurredAt: '2026-01-01T10:00:00+00:00',
            correlationId: 'corr-1',
        );
    }
}
