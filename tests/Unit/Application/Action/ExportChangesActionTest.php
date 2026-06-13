<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Action;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\Read\ExportChangesAction;
use Yammi\AuditLog\Application\DTO\Audit\AuditFilterData;
use Yammi\AuditLog\Application\Service\CriteriaFactory;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\Support\InMemoryAuditRecordRepository;

final class ExportChangesActionTest extends TestCase
{
    public function test_it_exports_filtered_entries_newest_first(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->record(ChangeType::Created, '2026-01-01'));
        $repository->save($this->record(ChangeType::Updated, '2026-01-02'));

        $action = new ExportChangesAction($repository, new CriteriaFactory);

        $entries = $action(new AuditFilterData);

        $this->assertCount(2, $entries);
        $this->assertSame('updated', $entries[0]->event);
        $this->assertSame('created', $entries[1]->event);
    }

    public function test_filters_narrow_the_export(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->record(ChangeType::Created, '2026-01-01'));
        $repository->save($this->record(ChangeType::Updated, '2026-01-02'));

        $action = new ExportChangesAction($repository, new CriteriaFactory);

        $entries = $action(new AuditFilterData(event: 'created'));

        $this->assertCount(1, $entries);
        $this->assertSame('created', $entries[0]->event);
    }

    private function record(ChangeType $event, string $date): AuditRecord
    {
        return new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 1),
            event: $event,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable($date.'T10:00:00+00:00'),
        );
    }
}
