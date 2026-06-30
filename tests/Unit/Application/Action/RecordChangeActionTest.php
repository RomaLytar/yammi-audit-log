<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Action;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\Record\RecordChangeAction;
use Yammi\AuditLog\Application\DTO\Audit\ChangeData;
use Yammi\AuditLog\Application\Pipeline\RecordChangePipeline;
use Yammi\AuditLog\Application\Pipeline\RecordChangeStage;
use Yammi\AuditLog\Application\Pipeline\Stage\ComputeDiffStage;
use Yammi\AuditLog\Application\Pipeline\Stage\ResolveActorStage;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\Span;
use Yammi\AuditLog\Tests\Support\FixedActorResolver;
use Yammi\AuditLog\Tests\Support\FixedClock;
use Yammi\AuditLog\Tests\Support\FixedCorrelationResolver;
use Yammi\AuditLog\Tests\Support\FixedSpanResolver;
use Yammi\AuditLog\Tests\Support\InMemoryAuditRecordRepository;
use Yammi\AuditLog\Tests\Support\StripKeysRedactor;

final class RecordChangeActionTest extends TestCase
{
    private InMemoryAuditRecordRepository $repository;

    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->repository = new InMemoryAuditRecordRepository;
        $this->now = new DateTimeImmutable('2026-01-01T12:00:00+00:00');
    }

    public function test_it_records_an_update_with_actor_origin_and_timestamp(): void
    {
        $action = $this->action([
            new ComputeDiffStage(new StripKeysRedactor([])),
            new ResolveActorStage(new FixedActorResolver(
                Actor::job('App\\Jobs\\ProcessPayment'),
                Actor::user('5', 'John Doe'),
            )),
        ]);

        $record = ($action)(new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1024',
            event: ChangeType::Updated,
            before: ['status' => 'pending'],
            after: ['status' => 'paid'],
        ));

        $this->assertNotNull($record);
        $this->assertCount(1, $this->repository->saved);
        $this->assertSame(ActorType::Job, $record->actor()->type);
        $this->assertSame('John Doe', $record->origin()?->displayLabel());
        $this->assertSame('paid', $record->diff()->field('status')?->new);
        $this->assertSame($this->now, $record->occurredAt());
        $this->assertSame('trace-test', $record->correlationId());
    }

    public function test_it_stamps_the_current_span_on_the_record(): void
    {
        $action = new RecordChangeAction(
            new RecordChangePipeline([new ComputeDiffStage(new StripKeysRedactor([]))]),
            $this->repository,
            new FixedClock($this->now),
            new FixedCorrelationResolver('trace-test'),
            span: new FixedSpanResolver(new Span('span-1', 'parent-span-1')),
        );

        $record = ($action)(new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: ChangeType::Created,
            before: [],
            after: ['status' => 'new'],
        ));

        $this->assertNotNull($record);
        $this->assertSame('span-1', $record->spanId());
        $this->assertSame('parent-span-1', $record->parentSpanId());
    }

    public function test_it_leaves_the_span_empty_when_no_resolver_provides_one(): void
    {
        $action = $this->action([new ComputeDiffStage(new StripKeysRedactor([]))]);

        $record = ($action)(new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: ChangeType::Created,
            before: [],
            after: ['status' => 'new'],
        ));

        $this->assertNotNull($record);
        $this->assertNull($record->spanId());
        $this->assertNull($record->parentSpanId());
    }

    public function test_it_skips_an_update_that_changed_nothing(): void
    {
        $action = $this->action([new ComputeDiffStage(new StripKeysRedactor([]))]);

        $record = ($action)(new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: ChangeType::Updated,
            before: ['status' => 'paid'],
            after: ['status' => 'paid'],
        ));

        $this->assertNull($record);
        $this->assertCount(0, $this->repository->saved);
    }

    public function test_it_records_a_creation_even_when_the_diff_is_empty(): void
    {
        $action = $this->action([new ComputeDiffStage(new StripKeysRedactor([]))]);

        $record = ($action)(new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: ChangeType::Created,
            before: [],
            after: [],
        ));

        $this->assertNotNull($record);
        $this->assertCount(1, $this->repository->saved);
        $this->assertSame(ChangeType::Created, $record->event());
    }

    public function test_it_falls_back_to_an_unknown_actor_when_no_stage_resolves_one(): void
    {
        $action = $this->action([new ComputeDiffStage(new StripKeysRedactor([]))]);

        $record = ($action)(new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: ChangeType::Created,
            before: [],
            after: ['status' => 'new'],
        ));

        $this->assertNotNull($record);
        $this->assertSame(ActorType::Unknown, $record->actor()->type);
        $this->assertNull($record->origin());
    }

    /**
     * @param  list<RecordChangeStage>  $stages
     */
    private function action(array $stages): RecordChangeAction
    {
        return new RecordChangeAction(
            new RecordChangePipeline($stages),
            $this->repository,
            new FixedClock($this->now),
            new FixedCorrelationResolver('trace-test'),
        );
    }
}
