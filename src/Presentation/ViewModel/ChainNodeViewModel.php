<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Yammi\AuditLog\Application\DTO\Audit\ChainNodeData;

/**
 * Presents one span of the causation tree for the trace view: its changes as
 * entry view models and its caused spans as nested node view models, so the
 * Blade partial can render itself recursively without any logic.
 *
 * @internal
 */
final class ChainNodeViewModel
{
    private const MAX_INDENT_DEPTH = 8;

    /** @var list<TimelineEntryViewModel> */
    public readonly array $entries;

    /** @var list<ChainNodeViewModel> */
    public readonly array $children;

    public function __construct(
        private readonly ChainNodeData $node,
        ?string $jobsMonitorUrl = null,
        ?string $timezone = null,
    ) {
        $entries = [];

        foreach ($node->entries as $entry) {
            $entries[] = new TimelineEntryViewModel($entry, 0, $jobsMonitorUrl, $timezone);
        }

        $this->entries = $entries;

        $children = [];

        foreach ($node->children as $child) {
            $children[] = new self($child, $jobsMonitorUrl, $timezone);
        }

        $this->children = $children;
    }

    public function depth(): int
    {
        return min(self::MAX_INDENT_DEPTH, $this->node->depth);
    }

    public function isRoot(): bool
    {
        return $this->node->depth === 0;
    }

    public function actorType(): string
    {
        return $this->node->actorType;
    }

    public function actorLabel(): string
    {
        return $this->node->actorLabel;
    }

    public function originLabel(): ?string
    {
        return $this->node->originLabel;
    }

    public function model(): string
    {
        return $this->node->model;
    }

    public function entryCount(): int
    {
        return count($this->entries);
    }

    public function hasChildren(): bool
    {
        return $this->children !== [];
    }

    public function processLabel(): string
    {
        return match ($this->node->actorType) {
            'user' => 'Request',
            'job' => 'Queued job',
            'command' => 'Console command',
            'scheduler' => 'Scheduled task',
            'system' => 'System',
            default => 'Process',
        };
    }

    public function processIcon(): string
    {
        return match ($this->node->actorType) {
            'user' => 'globe',
            'job' => 'layers',
            'command' => 'terminal',
            'scheduler' => 'clock',
            default => 'cpu',
        };
    }
}
