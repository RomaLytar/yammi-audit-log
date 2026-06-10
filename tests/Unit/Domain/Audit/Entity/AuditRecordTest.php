<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Domain\Audit\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;

final class AuditRecordTest extends TestCase
{
    public function test_it_exposes_its_change_with_actor_and_origin(): void
    {
        $occurredAt = new DateTimeImmutable('2026-01-01T10:00:00+00:00');

        $record = new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 1024),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'pending'], ['status' => 'paid']),
            actor: Actor::job('App\\Jobs\\ProcessPayment'),
            origin: Actor::user('5', 'John Doe'),
            labels: new LabelSnapshot(['user_id' => 'John Doe']),
            occurredAt: $occurredAt,
        );

        $this->assertNull($record->id());
        $this->assertSame('App\\Models\\Order', $record->auditable()->type);
        $this->assertSame(ChangeType::Updated, $record->event());
        $this->assertSame('paid', $record->diff()->field('status')?->new);
        $this->assertSame('App\\Jobs\\ProcessPayment', $record->actor()->displayLabel());
        $this->assertSame('John Doe', $record->origin()?->displayLabel());
        $this->assertSame($occurredAt, $record->occurredAt());
        $this->assertTrue($record->hasIdentifiedActor());
    }

    public function test_a_system_actor_is_reported_as_anonymous(): void
    {
        $record = new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 1),
            event: ChangeType::Created,
            diff: Diff::empty(),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
            id: 99,
        );

        $this->assertSame(99, $record->id());
        $this->assertNull($record->origin());
        $this->assertFalse($record->hasIdentifiedActor());
    }
}
