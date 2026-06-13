<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Persistence;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Application\Contract\AuditLogQuery;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\TestCase;

final class EloquentAuditLogQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_actor_filter_matches_by_substring(): void
    {
        $this->saveRecordWithActorLabel('Jane Doe');
        $this->saveRecordWithActorLabel('John Smith');

        $paged = $this->query()->paginate(new AuditCriteria(actorLabel: 'Jane'));

        $this->assertSame(1, $paged->total);
        $this->assertSame('Jane Doe', $paged->records[0]->actor()->label);
    }

    public function test_the_actor_filter_treats_percent_literally(): void
    {
        $this->saveRecordWithActorLabel('Sale 100% Bot');
        $this->saveRecordWithActorLabel('Sale 100x Bot');

        $paged = $this->query()->paginate(new AuditCriteria(actorLabel: '100%'));

        $this->assertSame(1, $paged->total);
        $this->assertSame('Sale 100% Bot', $paged->records[0]->actor()->label);
    }

    public function test_the_actor_filter_treats_underscore_literally(): void
    {
        $this->saveRecordWithActorLabel('importer_v2');
        $this->saveRecordWithActorLabel('importerXv2');

        $paged = $this->query()->paginate(new AuditCriteria(actorLabel: 'importer_v2'));

        $this->assertSame(1, $paged->total);
        $this->assertSame('importer_v2', $paged->records[0]->actor()->label);
    }

    public function test_the_actor_filter_treats_the_escape_character_literally(): void
    {
        $this->saveRecordWithActorLabel('warn! bot');
        $this->saveRecordWithActorLabel('warnX bot');

        $paged = $this->query()->paginate(new AuditCriteria(actorLabel: 'warn!'));

        $this->assertSame(1, $paged->total);
        $this->assertSame('warn! bot', $paged->records[0]->actor()->label);
    }

    public function test_the_from_filter_includes_the_whole_starting_day(): void
    {
        $this->saveRecordAt(new DateTimeImmutable('2025-12-31T23:59:59+00:00'));
        $this->saveRecordAt(new DateTimeImmutable('2026-01-01T00:00:00+00:00'));
        $this->saveRecordAt(new DateTimeImmutable('2026-01-01T15:30:00+00:00'));

        $paged = $this->query()->paginate(new AuditCriteria(from: new DateTimeImmutable('2026-01-01')));

        $this->assertSame(2, $paged->total);
    }

    public function test_the_to_filter_includes_the_whole_ending_day(): void
    {
        $this->saveRecordAt(new DateTimeImmutable('2026-01-01T23:59:59+00:00'));
        $this->saveRecordAt(new DateTimeImmutable('2026-01-02T00:00:00+00:00'));

        $paged = $this->query()->paginate(new AuditCriteria(to: new DateTimeImmutable('2026-01-01')));

        $this->assertSame(1, $paged->total);
    }

    public function test_from_and_to_bound_a_single_day(): void
    {
        $this->saveRecordAt(new DateTimeImmutable('2025-12-31T12:00:00+00:00'));
        $this->saveRecordAt(new DateTimeImmutable('2026-01-01T12:00:00+00:00'));
        $this->saveRecordAt(new DateTimeImmutable('2026-01-02T12:00:00+00:00'));

        $paged = $this->query()->paginate(new AuditCriteria(
            from: new DateTimeImmutable('2026-01-01'),
            to: new DateTimeImmutable('2026-01-01'),
        ));

        $this->assertSame(1, $paged->total);
    }

    public function test_search_matches_old_and_new_values_in_the_diff(): void
    {
        $this->saveRecordWithDiff(['status' => 'pending'], ['status' => 'cancelled']);
        $this->saveRecordWithDiff(['status' => 'draft'], ['status' => 'published']);

        $this->assertSame(1, $this->query()->paginate(new AuditCriteria(search: 'cancelled'))->total);
        $this->assertSame(1, $this->query()->paginate(new AuditCriteria(search: 'pending'))->total);
        $this->assertSame(0, $this->query()->paginate(new AuditCriteria(search: 'refunded'))->total);
    }

    public function test_search_matches_the_auditable_id(): void
    {
        $this->saveRecordWithDiff(['status' => 'a'], ['status' => 'b'], auditableId: 777);
        $this->saveRecordWithDiff(['status' => 'a'], ['status' => 'b'], auditableId: 778);

        $paged = $this->query()->paginate(new AuditCriteria(search: '777'));

        $this->assertSame(1, $paged->total);
        $this->assertSame('777', $paged->records[0]->auditable()->id);
    }

    public function test_search_treats_wildcards_literally(): void
    {
        $this->saveRecordWithDiff(['code' => 'A-100%'], ['code' => 'B']);
        $this->saveRecordWithDiff(['code' => 'A-100x'], ['code' => 'B']);

        $this->assertSame(1, $this->query()->paginate(new AuditCriteria(search: '100%'))->total);
    }

    public function test_the_field_filter_matches_records_that_changed_that_field(): void
    {
        $this->saveRecordWithDiff(['status' => 'pending'], ['status' => 'cancelled']);
        $this->saveRecordWithDiff(['title' => 'a'], ['title' => 'b']);

        $this->assertSame(1, $this->query()->paginate(new AuditCriteria(field: 'status'))->total);
    }

    public function test_the_value_transition_filter_matches_a_specific_from_and_to(): void
    {
        $this->saveRecordWithDiff(['status' => 'pending'], ['status' => 'cancelled']);
        $this->saveRecordWithDiff(['status' => 'pending'], ['status' => 'approved']);
        $this->saveRecordWithDiff(['status' => 'active'], ['status' => 'cancelled']);

        $paged = $this->query()->paginate(new AuditCriteria(
            field: 'status',
            valueFrom: 'pending',
            valueTo: 'cancelled',
        ));

        $this->assertSame(1, $paged->total);
    }

    public function test_the_value_transition_filter_can_match_the_destination_only(): void
    {
        $this->saveRecordWithDiff(['status' => 'pending'], ['status' => 'cancelled']);
        $this->saveRecordWithDiff(['status' => 'active'], ['status' => 'cancelled']);
        $this->saveRecordWithDiff(['status' => 'active'], ['status' => 'approved']);

        $this->assertSame(2, $this->query()->paginate(new AuditCriteria(field: 'status', valueTo: 'cancelled'))->total);
    }

    private function query(): AuditLogQuery
    {
        return $this->app->make(AuditLogQuery::class);
    }

    /**
     * @param  array<string, scalar|null>  $before
     * @param  array<string, scalar|null>  $after
     */
    private function saveRecordWithDiff(array $before, array $after, int $auditableId = 1): void
    {
        $this->app->make(AuditRecordRepository::class)->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', $auditableId),
            event: ChangeType::Updated,
            diff: Diff::between($before, $after),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        ));
    }

    private function saveRecordAt(DateTimeImmutable $occurredAt): void
    {
        $this->app->make(AuditRecordRepository::class)->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 1),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: $occurredAt,
        ));
    }

    private function saveRecordWithActorLabel(string $label): void
    {
        $this->app->make(AuditRecordRepository::class)->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 1),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: Actor::user('1', $label),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        ));
    }
}
