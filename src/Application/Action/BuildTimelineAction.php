<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action;

use Yammi\AuditLog\Application\DTO\TimelineData;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

/** @internal */
final class BuildTimelineAction
{
    public function __construct(
        private readonly AuditRecordRepository $repository,
    ) {}

    public function __invoke(AuditableReference $auditable, int $limit = 50): TimelineData
    {
        $entries = TimelineEntryData::fromRecords($this->repository->timelineFor($auditable, $limit));

        return new TimelineData($auditable->type, $auditable->id, $entries);
    }
}
