<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Action;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\BuildSubjectReportAction;
use Yammi\AuditLog\Application\DTO\SubjectReportData;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\Support\FixedClock;
use Yammi\AuditLog\Tests\Support\InMemoryAuditRecordRepository;

final class BuildSubjectReportActionTest extends TestCase
{
    private InMemoryAuditRecordRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryAuditRecordRepository;
    }

    public function test_it_separates_changes_to_the_record_from_changes_by_the_subject(): void
    {
        $this->save('App\\Models\\User', '5', ChangeType::Updated, Actor::system(), '2026-01-01 10:00:00');
        $this->save('App\\Models\\Order', '42', ChangeType::Updated, Actor::user('5', 'Jane'), '2026-01-02 10:00:00');
        $this->save('App\\Models\\Order', '43', ChangeType::Created, Actor::user('7', 'Other'), '2026-01-03 10:00:00');

        $report = $this->build();

        $this->assertCount(1, $report->recordChanges);
        $this->assertSame('App\\Models\\User', $report->recordChanges[0]->auditableType);

        $this->assertCount(1, $report->actorChanges);
        $this->assertSame('App\\Models\\Order', $report->actorChanges[0]->auditableType);
        $this->assertSame('42', $report->actorChanges[0]->auditableId);
    }

    public function test_a_job_with_the_same_identifier_is_not_attributed_to_the_user(): void
    {
        $this->save('App\\Models\\Order', '42', ChangeType::Updated, Actor::job('5'), '2026-01-02 10:00:00');

        $report = $this->build();

        $this->assertSame([], $report->actorChanges);
    }

    public function test_an_empty_subject_yields_an_empty_report(): void
    {
        $report = $this->build();

        $this->assertTrue($report->isEmpty());
        $this->assertFalse($report->truncated);
        $this->assertSame('User', $report->model());
    }

    private function build(): SubjectReportData
    {
        $action = new BuildSubjectReportAction(
            $this->repository,
            new FixedClock(new DateTimeImmutable('2026-06-01T00:00:00+00:00')),
        );

        return $action(AuditableReference::to('App\\Models\\User', '5'));
    }

    private function save(string $type, string $id, ChangeType $event, Actor $actor, string $occurredAt): void
    {
        $this->repository->save(new AuditRecord(
            auditable: AuditableReference::to($type, $id),
            event: $event,
            diff: Diff::between([], ['marker' => 'value']),
            actor: $actor,
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable($occurredAt),
        ));
    }
}
