<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Persistence;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Infrastructure\Persistence\Mapper\AuditRecordMapper;
use Yammi\AuditLog\Infrastructure\Persistence\Repository\EloquentAuditRecordRepository;
use Yammi\AuditLog\Tests\TestCase;

final class EloquentAuditRecordRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_records_and_returns_them_newest_first(): void
    {
        $repository = $this->app->make(AuditRecordRepository::class);
        $reference = AuditableReference::to('App\\Models\\Order', 7);

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
            labels: new LabelSnapshot(['user_id' => 'John Doe']),
            occurredAt: new DateTimeImmutable('2026-01-01T11:00:00+00:00'),
        ));

        $timeline = $repository->timelineFor($reference);

        $this->assertCount(2, $timeline);
        $this->assertSame(ChangeType::Updated, $timeline[0]->event());
        $this->assertSame('paid', $timeline[0]->diff()->field('status')?->new);
        $this->assertSame('App\\Jobs\\ProcessPayment', $timeline[0]->actor()->displayLabel());
        $this->assertSame('John Doe', $timeline[0]->origin()?->displayLabel());
        $this->assertSame('John Doe', $timeline[0]->labels()->for('user_id'));
        $this->assertSame(ChangeType::Created, $timeline[1]->event());
        $this->assertNull($timeline[1]->origin());
    }

    public function test_it_only_returns_records_for_the_requested_auditable(): void
    {
        $repository = $this->app->make(AuditRecordRepository::class);

        $repository->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 1),
            event: ChangeType::Created,
            diff: Diff::empty(),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        ));

        $other = AuditableReference::to('App\\Models\\Order', 2);

        $this->assertCount(0, $repository->timelineFor($other));
    }

    public function test_prune_deletes_across_multiple_chunks(): void
    {
        $repository = new EloquentAuditRecordRepository(
            $this->app->make(AuditRecordMapper::class),
            pruneChunkSize: 2,
        );

        foreach (range(1, 5) as $hour) {
            $repository->save($this->recordAt(new DateTimeImmutable("2020-01-01T0{$hour}:00:00+00:00")));
        }

        $repository->save($this->recordAt(new DateTimeImmutable('2026-06-01T10:00:00+00:00')));

        $deleted = $repository->deleteOlderThan(new DateTimeImmutable('2026-01-01T00:00:00+00:00'));

        $this->assertSame(5, $deleted);
        $this->assertSame(1, AuditRecordModel::query()->count());
    }

    public function test_prune_deletes_nothing_when_all_records_are_recent(): void
    {
        $repository = $this->app->make(AuditRecordRepository::class);
        $repository->save($this->recordAt(new DateTimeImmutable('2026-06-01T10:00:00+00:00')));

        $this->assertSame(0, $repository->deleteOlderThan(new DateTimeImmutable('2026-01-01T00:00:00+00:00')));
        $this->assertSame(1, AuditRecordModel::query()->count());
    }

    private function recordAt(DateTimeImmutable $occurredAt): AuditRecord
    {
        return new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 1),
            event: ChangeType::Created,
            diff: Diff::empty(),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: $occurredAt,
        );
    }
}
