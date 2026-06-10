<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\DTO;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;

final class TimelineEntryDataTest extends TestCase
{
    public function test_it_maps_every_field_from_a_record(): void
    {
        $entry = TimelineEntryData::fromRecord(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 7),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: Actor::user('1', 'Jane'),
            origin: Actor::command('app:sync'),
            labels: new LabelSnapshot(['user_id' => 'Jane']),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
            correlationId: 'corr-1',
            isNoise: true,
            id: 12,
        ));

        $this->assertSame(12, $entry->id);
        $this->assertSame('App\\Models\\Order', $entry->auditableType);
        $this->assertSame('7', $entry->auditableId);
        $this->assertSame('updated', $entry->event);
        $this->assertSame('user', $entry->actorType);
        $this->assertSame('Jane', $entry->actorLabel);
        $this->assertSame('app:sync', $entry->originLabel);
        $this->assertSame(['status' => ['old' => 'a', 'new' => 'b']], $entry->changes);
        $this->assertSame(['user_id' => 'Jane'], $entry->labels);
        $this->assertSame('2026-01-01T10:00:00+00:00', $entry->occurredAt);
        $this->assertSame('corr-1', $entry->correlationId);
        $this->assertTrue($entry->isNoise);
    }

    public function test_a_missing_origin_maps_to_null(): void
    {
        $entry = TimelineEntryData::fromRecord(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 7),
            event: ChangeType::Created,
            diff: Diff::empty(),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        ));

        $this->assertNull($entry->originLabel);
        $this->assertNull($entry->id);
        $this->assertFalse($entry->isNoise);
    }

    public function test_model_returns_the_class_basename(): void
    {
        $entry = TimelineEntryData::fromRecord($this->recordFor('App\\Models\\Order'));

        $this->assertSame('Order', $entry->model());
    }

    public function test_model_falls_back_to_the_full_type_without_a_namespace(): void
    {
        $entry = TimelineEntryData::fromRecord($this->recordFor('orders'));

        $this->assertSame('orders', $entry->model());
    }

    private function recordFor(string $type): AuditRecord
    {
        return new AuditRecord(
            auditable: AuditableReference::to($type, 1),
            event: ChangeType::Created,
            diff: Diff::empty(),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        );
    }
}
