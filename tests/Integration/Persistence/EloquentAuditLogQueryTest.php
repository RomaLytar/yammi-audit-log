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

    private function query(): AuditLogQuery
    {
        return $this->app->make(AuditLogQuery::class);
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
