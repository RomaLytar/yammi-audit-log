<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Action;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\Read\BuildChainAction;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\Support\InMemoryAuditRecordRepository;

final class BuildChainActionTest extends TestCase
{
    public function test_it_builds_a_chain_across_models_from_one_correlation(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->record('App\\Models\\Order', Actor::user('5', 'Jane Doe'), 'trace-1'));
        $repository->save($this->record('App\\Models\\Invoice', Actor::job('App\\Jobs\\ProcessOrder'), 'trace-1'));
        $repository->save($this->record('App\\Models\\Payment', Actor::job('App\\Jobs\\ProcessOrder'), 'trace-1'));
        $repository->save($this->record('App\\Models\\Order', Actor::system(), 'other-trace'));

        $chain = (new BuildChainAction($repository))('trace-1');

        $this->assertNotNull($chain);
        $this->assertSame(3, $chain->count());
        $this->assertSame(3, $chain->modelCount);
        $this->assertSame('Jane Doe', $chain->rootActorLabel);
        $this->assertSame('Order', $chain->rootModel);
    }

    public function test_an_unknown_correlation_returns_null(): void
    {
        $this->assertNull((new BuildChainAction(new InMemoryAuditRecordRepository))('nope'));
    }

    public function test_it_nests_spans_into_a_causation_tree(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->record('App\\Models\\Order', Actor::user('5', 'Jane Doe'), 'trace-1', 'req', null));
        $repository->save($this->record('App\\Models\\Payment', Actor::job('App\\Jobs\\Pay'), 'trace-1', 'job-a', 'req'));
        $repository->save($this->record('App\\Models\\Invoice', Actor::job('App\\Jobs\\Invoice'), 'trace-1', 'job-b', 'job-a'));
        $repository->save($this->record('App\\Models\\Note', Actor::job('App\\Jobs\\Notify'), 'trace-1', 'job-c', 'req'));

        $chain = (new BuildChainAction($repository))('trace-1');

        $this->assertNotNull($chain);
        $this->assertCount(1, $chain->tree);

        $root = $chain->tree[0];
        $this->assertSame('req', $root->spanId);
        $this->assertNull($root->parentSpanId);
        $this->assertSame(0, $root->depth);
        $this->assertCount(1, $root->entries);
        $this->assertCount(2, $root->children);

        $jobA = $root->children[0];
        $this->assertSame('job-a', $jobA->spanId);
        $this->assertSame(1, $jobA->depth);
        $this->assertCount(1, $jobA->children);
        $this->assertSame('job-b', $jobA->children[0]->spanId);
        $this->assertSame(2, $jobA->children[0]->depth);

        $this->assertSame('job-c', $root->children[1]->spanId);
        $this->assertCount(0, $root->children[1]->children);
    }

    public function test_records_without_spans_collapse_into_one_root(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->record('App\\Models\\Order', Actor::system(), 'trace-1', null, null));
        $repository->save($this->record('App\\Models\\Order', Actor::system(), 'trace-1', null, null));

        $chain = (new BuildChainAction($repository))('trace-1');

        $this->assertNotNull($chain);
        $this->assertCount(1, $chain->tree);
        $this->assertNull($chain->tree[0]->spanId);
        $this->assertCount(2, $chain->tree[0]->entries);
    }

    public function test_a_span_whose_parent_is_absent_becomes_a_root(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->record('App\\Models\\Order', Actor::job('App\\Jobs\\X'), 'trace-1', 'job-x', 'missing-parent'));

        $chain = (new BuildChainAction($repository))('trace-1');

        $this->assertNotNull($chain);
        $this->assertCount(1, $chain->tree);
        $this->assertSame('job-x', $chain->tree[0]->spanId);
        $this->assertSame(0, $chain->tree[0]->depth);
    }

    private function record(string $type, Actor $actor, string $correlationId, ?string $spanId = null, ?string $parentSpanId = null): AuditRecord
    {
        return new AuditRecord(
            auditable: AuditableReference::to($type, 1),
            event: ChangeType::Created,
            diff: Diff::empty(),
            actor: $actor,
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
            correlationId: $correlationId,
            spanId: $spanId,
            parentSpanId: $parentSpanId,
        );
    }
}
