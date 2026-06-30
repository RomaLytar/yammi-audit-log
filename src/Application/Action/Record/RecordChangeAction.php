<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action\Record;

use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\Contract\Resolver\CorrelationResolver;
use Yammi\AuditLog\Application\Contract\Resolver\ReasonResolver;
use Yammi\AuditLog\Application\Contract\Resolver\SpanResolver;
use Yammi\AuditLog\Application\DTO\Audit\ChangeData;
use Yammi\AuditLog\Application\Pipeline\RecordChangeContext;
use Yammi\AuditLog\Application\Pipeline\RecordChangePipeline;
use Yammi\AuditLog\Application\Service\NullReasonResolver;
use Yammi\AuditLog\Application\Service\NullSpanResolver;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;

/** @internal */
final class RecordChangeAction
{
    public function __construct(
        private readonly RecordChangePipeline $pipeline,
        private readonly AuditRecordRepository $repository,
        private readonly Clock $clock,
        private readonly CorrelationResolver $correlation,
        private readonly ReasonResolver $reason = new NullReasonResolver,
        private readonly SpanResolver $span = new NullSpanResolver,
    ) {}

    public function __invoke(ChangeData $change): ?AuditRecord
    {
        $context = $this->pipeline->process(RecordChangeContext::start($change));

        if ($change->event === ChangeType::Updated && $context->diff->isEmpty()) {
            return null;
        }

        $span = $this->span->resolve();

        $record = new AuditRecord(
            auditable: $change->reference(),
            event: $change->event,
            diff: $context->diff,
            actor: $context->actor ?? Actor::unknown(),
            origin: $context->origin,
            labels: $context->labels,
            occurredAt: $this->clock->now(),
            correlationId: $this->correlation->resolve(),
            isNoise: $context->isNoise,
            context: $context->requestContext,
            chainDepth: $context->depth,
            reason: $this->reason->resolve(),
            spanId: $span?->id,
            parentSpanId: $span?->parentId,
        );

        $this->repository->save($record);

        return $record;
    }
}
