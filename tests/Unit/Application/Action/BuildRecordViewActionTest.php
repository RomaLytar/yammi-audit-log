<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Action;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\Read\BuildRecordViewAction;
use Yammi\AuditLog\Application\DTO\Audit\RecordViewData;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\Support\FixedClock;
use Yammi\AuditLog\Tests\Support\InMemoryAuditRecordRepository;

final class BuildRecordViewActionTest extends TestCase
{
    private InMemoryAuditRecordRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryAuditRecordRepository;
    }

    public function test_the_own_history_is_listed_newest_first(): void
    {
        $this->save('App\\Models\\Order', '42', ChangeType::Created, ['status' => 'new'], '2026-01-01 10:00:00');
        $this->save('App\\Models\\Order', '42', ChangeType::Updated, ['status' => 'paid'], '2026-02-01 10:00:00');

        $view = $this->build();

        $this->assertCount(2, $view->entries);
        $this->assertSame('updated', $view->entries[0]->event);
        $this->assertSame('created', $view->entries[1]->event);
        $this->assertSame('order_id', $view->referenceField);
    }

    public function test_chain_neighbours_are_related(): void
    {
        $this->save('App\\Models\\Order', '42', ChangeType::Created, ['status' => 'new'], '2026-01-01 10:00:00', 'corr-1');
        $this->save('App\\Models\\Invoice', '7', ChangeType::Created, ['total' => 100], '2026-01-01 10:00:01', 'corr-1');
        $this->save('App\\Models\\Coupon', '9', ChangeType::Created, ['code' => 'X'], '2026-01-05 10:00:00', 'corr-other');

        $view = $this->build();

        $this->assertCount(1, $view->related);
        $this->assertSame('App\\Models\\Invoice', $view->related[0]->entry->auditableType);
        $this->assertSame('chain', $view->related[0]->via);
    }

    public function test_foreign_key_references_are_related(): void
    {
        $this->save('App\\Models\\Order', '42', ChangeType::Created, ['status' => 'new'], '2026-01-01 10:00:00');
        $this->save('App\\Models\\Shipment', '3', ChangeType::Created, ['order_id' => 42], '2026-01-02 10:00:00');
        $this->save('App\\Models\\Shipment', '4', ChangeType::Created, ['order_id' => 99], '2026-01-03 10:00:00');

        $view = $this->build();

        $this->assertCount(1, $view->related);
        $this->assertSame('App\\Models\\Shipment', $view->related[0]->entry->auditableType);
        $this->assertSame('3', $view->related[0]->entry->auditableId);
        $this->assertSame('reference', $view->related[0]->via);
    }

    public function test_a_chain_neighbour_is_not_duplicated_as_a_reference(): void
    {
        $this->save('App\\Models\\Order', '42', ChangeType::Created, ['status' => 'new'], '2026-01-01 10:00:00', 'corr-1');
        $this->save('App\\Models\\Shipment', '3', ChangeType::Created, ['order_id' => 42], '2026-01-01 10:00:01', 'corr-1');

        $view = $this->build();

        $this->assertCount(1, $view->related);
        $this->assertSame('chain', $view->related[0]->via);
    }

    public function test_a_multi_word_model_derives_a_snake_case_reference_field(): void
    {
        $action = new BuildRecordViewAction(
            $this->repository,
            new FixedClock(new DateTimeImmutable('2026-06-01T00:00:00+00:00')),
        );

        $view = $action(AuditableReference::to('App\\Models\\StockItem', '5'));

        $this->assertSame('stock_item_id', $view->referenceField);
        $this->assertTrue($view->isEmpty());
    }

    private function build(): RecordViewData
    {
        $action = new BuildRecordViewAction(
            $this->repository,
            new FixedClock(new DateTimeImmutable('2026-06-01T00:00:00+00:00')),
        );

        return $action(AuditableReference::to('App\\Models\\Order', '42'));
    }

    /**
     * @param  array<string, scalar|null>  $after
     */
    private function save(string $type, string $id, ChangeType $event, array $after, string $occurredAt, ?string $correlationId = null): void
    {
        $this->repository->save(new AuditRecord(
            auditable: AuditableReference::to($type, $id),
            event: $event,
            diff: Diff::between([], $after),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable($occurredAt),
            correlationId: $correlationId,
        ));
    }
}
