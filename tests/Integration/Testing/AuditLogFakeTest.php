<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Testing;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class AuditLogFakeTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
    }

    public function test_fake_captures_automatic_changes_and_asserts_them(): void
    {
        $fake = AuditLog::fake();

        $post = Post::create(['title' => 'A', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        AuditLog::assertRecorded(Post::class, $post->getKey(), 'created');
        AuditLog::assertRecorded(
            Post::class,
            $post->getKey(),
            'updated',
            fn (AuditRecord $record): bool => $record->diff()->field('status')?->new === 'published',
        );
        AuditLog::assertRecordedCount(2);
        $fake->assertRecorded(Post::class);
    }

    public function test_fake_captures_manual_records(): void
    {
        AuditLog::fake();

        AuditLog::record(Post::class, 7, 'updated', ['status' => 'a'], ['status' => 'b']);

        AuditLog::assertRecorded(Post::class, 7, 'updated');
        AuditLog::assertRecordedCount(1);
    }

    public function test_assert_not_recorded_for_an_untouched_model(): void
    {
        AuditLog::fake();

        Post::create(['title' => 'A', 'status' => 'draft']);

        AuditLog::assertNotRecorded('App\\Models\\Order');
    }

    public function test_assert_nothing_recorded_when_no_change_happens(): void
    {
        AuditLog::fake();

        AuditLog::assertNothingRecorded();
    }

    public function test_asserting_without_calling_fake_throws(): void
    {
        $this->expectException(RuntimeException::class);

        AuditLog::assertNothingRecorded();
    }
}
