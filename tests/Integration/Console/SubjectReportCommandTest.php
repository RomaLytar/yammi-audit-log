<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Console;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\TestCase;

final class SubjectReportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_report_is_written_as_ndjson_with_both_sections(): void
    {
        Storage::fake('local');
        $this->seedSubjectData();

        $this->artisan('audit-log:subject-report', ['model' => 'App\\Models\\User', 'id' => '5'])
            ->expectsOutputToContain('1 change(s) to the record, 1 made by it')
            ->assertSuccessful();

        $files = Storage::disk('local')->files('audit-log');
        $this->assertCount(1, $files);
        $this->assertStringContainsString('subject-report-user-5', $files[0]);

        $content = (string) Storage::disk('local')->get($files[0]);
        $this->assertStringContainsString('"report":"subject-access"', $content);
        $this->assertStringContainsString('"section":"record_changes"', $content);
        $this->assertStringContainsString('"section":"actor_changes"', $content);
        $this->assertStringContainsString('email-changed', $content);
        $this->assertStringContainsString('order-cancelled', $content);
    }

    public function test_the_report_can_be_written_as_html(): void
    {
        Storage::fake('local');
        $this->seedSubjectData();

        $this->artisan('audit-log:subject-report', [
            'model' => 'App\\Models\\User',
            'id' => '5',
            '--format' => 'html',
        ])->assertSuccessful();

        $files = Storage::disk('local')->files('audit-log');
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.html', $files[0]);

        $content = (string) Storage::disk('local')->get($files[0]);
        $this->assertStringContainsString('Subject access report', $content);
        $this->assertStringContainsString('App\\Models\\User #5', $content);
        $this->assertStringContainsString('Changes performed by this subject', $content);
    }

    public function test_an_unknown_format_is_rejected(): void
    {
        $this->artisan('audit-log:subject-report', [
            'model' => 'App\\Models\\User',
            'id' => '5',
            '--format' => 'pdf',
        ])->assertFailed();
    }

    private function seedSubjectData(): void
    {
        $repository = $this->app->make(AuditRecordRepository::class);

        $repository->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\User', '5'),
            event: ChangeType::Updated,
            diff: Diff::between(['email' => 'old@example.com'], ['email' => 'email-changed']),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01 10:00:00'),
        ));

        $repository->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', '42'),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'pending'], ['status' => 'order-cancelled']),
            actor: Actor::user('5', 'Jane Doe'),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-02 10:00:00'),
        ));
    }
}
