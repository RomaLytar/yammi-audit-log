<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Yammi\AuditLog\Application\DTO\ChainData;

/** @internal */
final class TraceViewModel
{
    /** @var list<TimelineEntryViewModel> */
    public readonly array $entries;

    public function __construct(
        private readonly ChainData $chain,
    ) {
        $entries = [];

        foreach ($chain->entries as $entry) {
            $entries[] = new TimelineEntryViewModel($entry, 0);
        }

        $this->entries = $entries;
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
}
