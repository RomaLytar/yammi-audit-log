<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Facade;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Tests\TestCase;

final class SubjectReportFacadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_facade_builds_the_subject_report(): void
    {
        $this->seedAuditData();

        $report = AuditLog::subjectReport('App\\Models\\User', '5');

        $this->assertCount(1, $report->recordChanges);
        $this->assertCount(1, $report->actorChanges);
        $this->assertSame('42', $report->actorChanges[0]->auditableId);
    }

    public function test_the_playground_executes_subject_report(): void
    {
        $this->seedAuditData();

        $response = $this->postJson(route('audit-log.playground.execute'), [
            'method' => 'subjectReport',
            'args' => [
                'auditable_type' => 'App\\Models\\User',
                'auditable_id' => '5',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('result.auditableId', '5');
        $response->assertJsonPath('result.actorChanges.0.auditableId', '42');
    }

    public function test_the_playground_page_lists_subject_report(): void
    {
        $this->get('audit-log/settings/playground')
            ->assertOk()
            ->assertSee('AuditLog::subjectReport');
    }

    private function seedAuditData(): void
    {
        $repository = $this->app->make(AuditRecordRepository::class);

        $repository->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\User', '5'),
            event: ChangeType::Updated,
            diff: Diff::between(['email' => 'a@example.com'], ['email' => 'b@example.com']),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01 10:00:00'),
        ));

        $repository->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', '42'),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'pending'], ['status' => 'cancelled']),
            actor: Actor::user('5', 'Jane Doe'),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-02 10:00:00'),
        ));
    }
}
