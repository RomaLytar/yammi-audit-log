<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Testing;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Infrastructure\Testing\AuditLogFake;

final class AuditLogFakeTest extends TestCase
{
    public function test_it_records_and_filters_by_type_id_event_and_matcher(): void
    {
        $fake = new AuditLogFake;
        $fake->save($this->record('App\\Models\\Order', 1, ChangeType::Created));
        $fake->save($this->record('App\\Models\\Order', 1, ChangeType::Updated));
        $fake->save($this->record('App\\Models\\Invoice', 2, ChangeType::Created));

        $fake->assertRecorded('App\\Models\\Order');
        $fake->assertRecorded('App\\Models\\Order', 1, 'updated');
        $fake->assertRecorded('App\\Models\\Order', 1, ChangeType::Updated, fn (AuditRecord $r): bool => $r->diff()->field('status')?->new === 'b');
        $fake->assertNotRecorded('App\\Models\\Order', 1, 'deleted');
        $fake->assertNotRecorded('App\\Models\\Ghost');
        $fake->assertRecordedCount(3);

        $this->assertCount(2, $fake->recorded('App\\Models\\Order'));
        $this->assertCount(1, $fake->recorded('App\\Models\\Order', 1, 'updated'));
        $this->assertCount(3, $fake->all());
    }

    public function test_timeline_for_and_delete_older_than(): void
    {
        $fake = new AuditLogFake;
        $fake->save($this->record('App\\Models\\Order', 1, ChangeType::Created));

        $this->assertCount(1, $fake->timelineFor(AuditableReference::to('App\\Models\\Order', 1)));
        $this->assertCount(0, $fake->timelineFor(AuditableReference::to('App\\Models\\Order', 99)));
        $this->assertSame(0, $fake->deleteOlderThan(new DateTimeImmutable('2030-01-01')));
    }

    public function test_assert_nothing_recorded_on_an_empty_fake(): void
    {
        (new AuditLogFake)->assertNothingRecorded();
    }

    private function record(string $type, int $id, ChangeType $event): AuditRecord
    {
        return new AuditRecord(
            auditable: AuditableReference::to($type, $id),
            event: $event,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: Actor::user('5', 'Jane'),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        );
    }
}
