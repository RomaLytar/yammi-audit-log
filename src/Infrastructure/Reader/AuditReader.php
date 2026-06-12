<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Reader;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Application\Action\BuildRecordViewAction;
use Yammi\AuditLog\Application\Action\BuildSubjectReportAction;
use Yammi\AuditLog\Application\Action\BuildTimelineAction;
use Yammi\AuditLog\Application\Action\ReconstructStateAction;
use Yammi\AuditLog\Application\DTO\RecordViewData;
use Yammi\AuditLog\Application\DTO\StateData;
use Yammi\AuditLog\Application\DTO\SubjectReportData;
use Yammi\AuditLog\Application\DTO\TimelineData;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

/** @internal */
final class AuditReader
{
    public function __construct(
        private readonly BuildTimelineAction $buildTimeline,
        private readonly ReconstructStateAction $reconstructState,
        private readonly BuildSubjectReportAction $buildSubjectReport,
        private readonly BuildRecordViewAction $buildRecordView,
    ) {}

    public function for(Model|string $auditable, int|string|null $id = null, int $limit = 50): TimelineData
    {
        return ($this->buildTimeline)($this->referenceFor($auditable, $id), $limit);
    }

    public function stateAt(Model|string $auditable, int|string|null $id, DateTimeImmutable $at): StateData
    {
        return ($this->reconstructState)($this->referenceFor($auditable, $id), $at);
    }

    public function subjectReport(Model|string $auditable, int|string|null $id = null): SubjectReportData
    {
        return ($this->buildSubjectReport)($this->referenceFor($auditable, $id));
    }

    public function recordView(Model|string $auditable, int|string|null $id = null): RecordViewData
    {
        return ($this->buildRecordView)($this->referenceFor($auditable, $id));
    }

    private function referenceFor(Model|string $auditable, int|string|null $id): AuditableReference
    {
        return $auditable instanceof Model
            ? AuditableReference::to($auditable->getMorphClass(), (string) $auditable->getKey())
            : AuditableReference::to($auditable, (string) $id);
    }
}
