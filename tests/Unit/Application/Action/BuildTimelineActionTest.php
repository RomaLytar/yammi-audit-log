<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Action;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\Read\BuildTimelineAction;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\Support\InMemoryAuditRecordRepository;

final class BuildTimelineActionTest extends TestCase
{
    public function test_it_builds_a_timeline_of_entries_newest_first(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $reference = AuditableReference::to('App\\Models\\Order', 1);

        $repository->save(new AuditRecord(
            auditable: $reference,
            event: ChangeType::Created,
            diff: Diff::between([], ['status' => 'new']),
            actor: Actor::user('5', 'John Doe'),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        ));

        $repository->save(new AuditRecord(
            auditable: $reference,
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'new'], ['status' => 'paid']),
            actor: Actor::job('App\\Jobs\\ProcessPayment'),
            origin: Actor::user('5', 'John Doe'),
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T11:00:00+00:00'),
        ));

        $timeline = (new BuildTimelineAction($repository))($reference);

        $this->assertSame('App\\Models\\Order', $timeline->auditableType);
        $this->assertSame('1', $timeline->auditableId);
        $this->assertSame(2, $timeline->count());
        $this->assertFalse($timeline->isEmpty());

        $latest = $timeline->entries[0];
        $this->assertSame('updated', $latest->event);
        $this->assertSame('job', $latest->actorType);
        $this->assertSame('App\\Jobs\\ProcessPayment', $latest->actorLabel);
        $this->assertSame('John Doe', $latest->originLabel);
        $this->assertSame('paid', $latest->changes['status']['new']);

        $this->assertSame('created', $timeline->entries[1]->event);
    }

    public function test_an_unknown_auditable_has_an_empty_timeline(): void
    {
        $timeline = (new BuildTimelineAction(new InMemoryAuditRecordRepository))(
            AuditableReference::to('App\\Models\\Order', 99),
        );

        $this->assertTrue($timeline->isEmpty());
        $this->assertSame(0, $timeline->count());
    }
}
