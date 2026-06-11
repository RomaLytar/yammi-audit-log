<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Action;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\ListChangesAction;
use Yammi\AuditLog\Application\DTO\AuditFilterData;
use Yammi\AuditLog\Application\Service\CriteriaFactory;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\Support\InMemoryAuditRecordRepository;

final class ListChangesActionTest extends TestCase
{
    private InMemoryAuditRecordRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryAuditRecordRepository;
        $this->repository->save($this->record('App\\Models\\Order', ChangeType::Created, Actor::user('1', 'Jane')));
        $this->repository->save($this->record('App\\Models\\Order', ChangeType::Updated, Actor::job('App\\Jobs\\Process')));
        $this->repository->save($this->record('App\\Models\\Product', ChangeType::Deleted, Actor::system()));
    }

    public function test_it_lists_every_change_with_filter_options(): void
    {
        $list = (new ListChangesAction($this->repository, new CriteriaFactory))(new AuditFilterData);

        $this->assertSame(3, $list->total);
        $this->assertCount(3, $list->entries);
        $this->assertContains('App\\Models\\Order', $list->models);
        $this->assertContains('job', $list->actorTypes);
        $this->assertSame(['created', 'updated', 'deleted', 'restored'], $list->events);
    }

    public function test_it_filters_by_event(): void
    {
        $list = (new ListChangesAction($this->repository, new CriteriaFactory))(new AuditFilterData(event: 'updated'));

        $this->assertSame(1, $list->total);
        $this->assertSame('updated', $list->entries[0]->event);
    }

    public function test_it_filters_by_actor_type(): void
    {
        $list = (new ListChangesAction($this->repository, new CriteriaFactory))(new AuditFilterData(actorType: 'job'));

        $this->assertSame(1, $list->total);
        $this->assertSame('job', $list->entries[0]->actorType);
    }

    public function test_an_invalid_event_filter_is_ignored(): void
    {
        $list = (new ListChangesAction($this->repository, new CriteriaFactory))(new AuditFilterData(event: 'not-an-event'));

        $this->assertSame(3, $list->total);
    }

    public function test_it_reports_the_chain_size_of_correlated_changes(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->record('App\\Models\\Order', ChangeType::Created, Actor::system(), 'chain-1'));
        $repository->save($this->record('App\\Models\\Invoice', ChangeType::Created, Actor::system(), 'chain-1'));
        $repository->save($this->record('App\\Models\\Product', ChangeType::Created, Actor::system()));

        $list = (new ListChangesAction($repository, new CriteriaFactory))(new AuditFilterData);

        $this->assertSame(2, $list->chainSize('chain-1'));
        $this->assertSame(1, $list->chainSize(null));
    }

    public function test_it_can_list_only_noise(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->record('App\\Models\\Order', ChangeType::Updated, Actor::system(), null, true));
        $repository->save($this->record('App\\Models\\Order', ChangeType::Updated, Actor::system(), null, false));

        $list = (new ListChangesAction($repository, new CriteriaFactory))(new AuditFilterData, onlyNoise: true);

        $this->assertSame(1, $list->total);
        $this->assertTrue($list->entries[0]->isNoise);
    }

    public function test_a_reversed_date_range_is_normalised(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->recordOn('2026-01-02'));
        $repository->save($this->recordOn('2026-03-01'));

        // "from" later than "to" must behave as the range 2026-01-01 to 2026-01-10.
        $list = (new ListChangesAction($repository, new CriteriaFactory))(new AuditFilterData(from: '2026-01-10', to: '2026-01-01'));

        $this->assertSame(1, $list->total);
    }

    private function recordOn(string $date): AuditRecord
    {
        return new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 1),
            event: ChangeType::Created,
            diff: Diff::between([], ['status' => 'x']),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable($date.'T10:00:00+00:00'),
        );
    }

    private function record(string $type, ChangeType $event, Actor $actor, ?string $correlationId = null, bool $isNoise = false): AuditRecord
    {
        return new AuditRecord(
            auditable: AuditableReference::to($type, 1),
            event: $event,
            diff: Diff::between([], ['status' => 'x']),
            actor: $actor,
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
            correlationId: $correlationId,
            isNoise: $isNoise,
        );
    }
}
