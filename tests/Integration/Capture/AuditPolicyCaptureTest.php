<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Capture;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class AuditPolicyCaptureTest extends TestCase
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

    public function test_a_when_predicate_skips_capture(): void
    {
        AuditLog::policy(Post::class)->when(
            static fn (Post $post): bool => $post->getAttribute('status') !== 'draft',
        );

        Post::create(['title' => 'A', 'status' => 'draft']);     // skipped
        Post::create(['title' => 'B', 'status' => 'published']); // captured

        $this->assertSame(1, AuditRecordModel::query()->count());
    }

    public function test_ignored_fields_are_dropped_from_the_diff(): void
    {
        AuditLog::policy(Post::class)->ignore(['status']);

        Post::create(['title' => 'A', 'status' => 'draft']);

        $changes = AuditRecordModel::query()->value('changes');

        $this->assertIsArray($changes);
        $this->assertArrayHasKey('title', $changes);
        $this->assertArrayNotHasKey('status', $changes);
    }

    public function test_capture_is_unchanged_without_a_policy(): void
    {
        Post::create(['title' => 'A', 'status' => 'draft']);

        $changes = AuditRecordModel::query()->value('changes');

        $this->assertIsArray($changes);
        $this->assertArrayHasKey('status', $changes);
        $this->assertSame(1, AuditRecordModel::query()->count());
    }
}
