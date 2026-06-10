<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Capture;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Tests\Support\Models\Note;
use Yammi\AuditLog\Tests\TestCase;

final class NoiseAndSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_an_update_that_only_bumps_timestamps_is_not_recorded(): void
    {
        $note = Note::create(['title' => 'A', 'status' => 'draft']);

        $note->touch();

        $this->assertCount(1, $this->timelineFor($note));
    }

    public function test_a_real_update_is_recorded_without_the_timestamp_noise(): void
    {
        $note = Note::create(['title' => 'A', 'status' => 'draft']);

        $note->update(['status' => 'published']);

        $timeline = $this->timelineFor($note);

        $this->assertCount(2, $timeline);
        $this->assertSame(ChangeType::Updated, $timeline[0]->event());
        $this->assertTrue($timeline[0]->diff()->has('status'));
        $this->assertFalse($timeline[0]->diff()->has('updated_at'));
    }

    public function test_a_soft_delete_is_recorded_as_a_deletion(): void
    {
        $note = Note::create(['title' => 'A', 'status' => 'draft']);

        $note->delete();

        $this->assertContains(ChangeType::Deleted, $this->events($note));
    }

    public function test_a_restore_is_recorded(): void
    {
        $note = Note::create(['title' => 'A', 'status' => 'draft']);
        $note->delete();

        $note->restore();

        $this->assertContains(ChangeType::Restored, $this->events($note));
    }

    /**
     * @return list<AuditRecord>
     */
    private function timelineFor(Note $note): array
    {
        return $this->app->make(AuditRecordRepository::class)->timelineFor(
            AuditableReference::to($note->getMorphClass(), (string) $note->getKey()),
        );
    }

    /**
     * @return list<ChangeType>
     */
    private function events(Note $note): array
    {
        return array_map(
            static fn (AuditRecord $record): ChangeType => $record->event(),
            $this->timelineFor($note),
        );
    }
}
