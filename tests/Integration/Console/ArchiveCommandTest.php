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
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\TestCase;

final class ArchiveCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiring_records_are_written_as_ndjson(): void
    {
        Storage::fake('local');

        $this->seedRecordAt('-3 years', 'ancient-history');
        $this->seedRecordAt('-1 day', 'fresh-row');

        $this->artisan('audit-log:archive', ['--days' => 30])->assertSuccessful();

        $files = Storage::disk('local')->files('audit-log');
        $this->assertCount(1, $files);

        $content = (string) Storage::disk('local')->get($files[0]);
        $this->assertStringContainsString('ancient-history', $content);
        $this->assertStringNotContainsString('fresh-row', $content);

        $this->assertSame(2, AuditRecordModel::query()->count());
    }

    public function test_then_prune_deletes_the_archived_rows(): void
    {
        Storage::fake('local');

        $this->seedRecordAt('-3 years', 'ancient-history');
        $this->seedRecordAt('-1 day', 'fresh-row');

        $this->artisan('audit-log:archive', ['--days' => 30, '--then-prune' => true])->assertSuccessful();

        $this->assertSame(1, AuditRecordModel::query()->count());
        $this->assertStringContainsString(
            'fresh-row',
            (string) json_encode(AuditRecordModel::query()->firstOrFail()->getAttribute('changes')),
        );
    }

    public function test_nothing_to_archive_writes_no_file(): void
    {
        Storage::fake('local');

        $this->seedRecordAt('-1 day', 'fresh-row');

        $this->artisan('audit-log:archive', ['--days' => 30])
            ->expectsOutputToContain('nothing archived')
            ->assertSuccessful();

        $this->assertSame([], Storage::disk('local')->files('audit-log'));
    }

    private function seedRecordAt(string $offset, string $marker): void
    {
        $this->app->make(AuditRecordRepository::class)->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 1),
            event: ChangeType::Created,
            diff: Diff::between([], ['status' => $marker]),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable($offset),
        ));
    }
}
