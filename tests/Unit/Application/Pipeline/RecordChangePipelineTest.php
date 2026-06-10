<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Pipeline;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\ChangeData;
use Yammi\AuditLog\Application\Pipeline\RecordChangeContext;
use Yammi\AuditLog\Application\Pipeline\RecordChangePipeline;
use Yammi\AuditLog\Application\Pipeline\Stage\ComputeDiffStage;
use Yammi\AuditLog\Application\Pipeline\Stage\ResolveActorStage;
use Yammi\AuditLog\Application\Pipeline\Stage\ResolveLabelsStage;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\Support\FixedActorResolver;
use Yammi\AuditLog\Tests\Support\StaticLabelResolver;
use Yammi\AuditLog\Tests\Support\StripKeysRedactor;

final class RecordChangePipelineTest extends TestCase
{
    public function test_it_runs_every_stage_and_accumulates_their_output(): void
    {
        $pipeline = new RecordChangePipeline([
            new ComputeDiffStage(new StripKeysRedactor([])),
            new ResolveActorStage(new FixedActorResolver(
                Actor::job('App\\Jobs\\ProcessPayment'),
                Actor::user('5', 'John Doe'),
            )),
            new ResolveLabelsStage(new StaticLabelResolver(new LabelSnapshot(['user_id' => 'John Doe']))),
        ]);

        $context = $pipeline->process(RecordChangeContext::start(new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1024',
            event: ChangeType::Updated,
            before: ['status' => 'pending'],
            after: ['status' => 'paid'],
        )));

        $this->assertSame('paid', $context->diff->field('status')?->new);
        $this->assertSame(ActorType::Job, $context->actor?->type);
        $this->assertSame('John Doe', $context->origin?->displayLabel());
        $this->assertSame('John Doe', $context->labels->for('user_id'));
    }

    public function test_an_empty_pipeline_returns_the_context_untouched(): void
    {
        $context = RecordChangeContext::start(new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: ChangeType::Created,
            before: [],
            after: ['status' => 'new'],
        ));

        $this->assertSame($context, (new RecordChangePipeline([]))->process($context));
    }
}
