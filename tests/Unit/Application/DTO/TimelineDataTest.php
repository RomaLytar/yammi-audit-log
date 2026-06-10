<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\DTO;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\TimelineData;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;

final class TimelineDataTest extends TestCase
{
    public function test_an_empty_timeline_reports_empty(): void
    {
        $timeline = new TimelineData('App\\Models\\Order', '1', []);

        $this->assertTrue($timeline->isEmpty());
        $this->assertSame(0, $timeline->count());
    }

    public function test_count_reflects_the_entries(): void
    {
        $entry = TimelineEntryData::fromRecord(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 1),
            event: ChangeType::Created,
            diff: Diff::empty(),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        ));

        $timeline = new TimelineData('App\\Models\\Order', '1', [$entry]);

        $this->assertFalse($timeline->isEmpty());
        $this->assertSame(1, $timeline->count());
    }
}
