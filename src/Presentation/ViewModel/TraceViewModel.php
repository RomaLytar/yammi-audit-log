<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Yammi\AuditLog\Application\DTO\Audit\ChainData;

/** @internal */
final class TraceViewModel
{
    /** @var list<TimelineEntryViewModel> */
    public readonly array $entries;

    /** @var list<ChainNodeViewModel> */
    public readonly array $tree;

    public function __construct(
        private readonly ChainData $chain,
        ?string $jobsMonitorUrl = null,
        ?string $timezone = null,
    ) {
        $entries = [];

        foreach ($chain->entries as $entry) {
            $entries[] = new TimelineEntryViewModel($entry, 0, $jobsMonitorUrl, $timezone);
        }

        $this->entries = $entries;

        $tree = [];

        foreach ($chain->tree as $node) {
            $tree[] = new ChainNodeViewModel($node, $jobsMonitorUrl, $timezone);
        }

        $this->tree = $tree;
    }

    public function correlationId(): string
    {
        return $this->chain->correlationId;
    }

    public function count(): int
    {
        return $this->chain->count();
    }

    public function modelCount(): int
    {
        return $this->chain->modelCount;
    }

    public function rootActorLabel(): string
    {
        return $this->chain->rootActorLabel;
    }

    public function rootModel(): string
    {
        return $this->chain->rootModel;
    }

    /**
     * How many nodes to fit across the canvas width: up to three so each stays
     * readable; wider trees scroll instead of shrinking further.
     */
    public function columns(): int
    {
        return max(1, min(3, $this->chain->maxBreadth));
    }
}
