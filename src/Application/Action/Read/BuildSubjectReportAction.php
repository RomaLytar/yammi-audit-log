<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action\Read;

use DateTimeInterface;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\Contract\Query\AuditLogQuery;
use Yammi\AuditLog\Application\DTO\Audit\SubjectReportData;
use Yammi\AuditLog\Application\DTO\Audit\TimelineEntryData;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

/**
 * Builds the GDPR subject access report: every recorded change to one
 * record, plus every change that record performed as an actor.
 *
 * @internal
 */
final class BuildSubjectReportAction
{
    public const SECTION_LIMIT = 10000;

    public function __construct(
        private readonly AuditLogQuery $query,
        private readonly Clock $clock,
    ) {}

    public function __invoke(AuditableReference $subject, ActorType $actorType = ActorType::User): SubjectReportData
    {
        $now = $this->clock->now();

        $recordChanges = TimelineEntryData::fromRecords(
            $this->query->historyFor($subject, $now, self::SECTION_LIMIT),
        );

        $actorChanges = TimelineEntryData::fromRecords(
            $this->query->byActor($actorType, $subject->id, self::SECTION_LIMIT),
        );

        return new SubjectReportData(
            auditableType: $subject->type,
            auditableId: $subject->id,
            generatedAt: $now->format(DateTimeInterface::ATOM),
            recordChanges: $recordChanges,
            actorChanges: $actorChanges,
            truncated: count($recordChanges) >= self::SECTION_LIMIT
                || count($actorChanges) >= self::SECTION_LIMIT,
        );
    }
}
