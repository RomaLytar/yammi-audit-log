<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action;

use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\DTO\ChangeData;
use Yammi\AuditLog\Application\Pipeline\RecordChangeContext;
use Yammi\AuditLog\Application\Pipeline\RecordChangePipeline;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;

final class RecordChangeAction
{
    public function __construct(
        private readonly RecordChangePipeline $pipeline,
        private readonly AuditRecordRepository $repository,
        private readonly Clock $clock,
    ) {}

    public function __invoke(ChangeData $change): ?AuditRecord
    {
        $context = $this->pipeline->process(RecordChangeContext::start($change));

        if ($change->event === ChangeType::Updated && $context->diff->isEmpty()) {
            return null;
        }

        $record = new AuditRecord(
            auditable: $change->reference(),
            event: $change->event,
            diff: $context->diff,
            actor: $context->actor ?? Actor::unknown(),
            origin: $context->origin,
            labels: $context->labels,
            occurredAt: $this->clock->now(),
        );

        $this->repository->save($record);

        return $record;
    }
}
