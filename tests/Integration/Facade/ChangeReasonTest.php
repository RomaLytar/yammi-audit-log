<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Facade;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class ChangeReasonTest extends TestCase
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

    public function test_with_reason_attaches_a_reason_to_manual_records(): void
    {
        $entry = AuditLog::withReason('ticket #4521', static fn () => AuditLog::record(
            'App\\Models\\Order',
            1,
            'updated',
            ['status' => 'paid'],
            ['status' => 'refunded'],
        ));

        $this->assertNotNull($entry);
        $this->assertSame('ticket #4521', $entry->reason);
    }

    public function test_with_reason_attaches_a_reason_to_captured_changes(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        AuditRecordModel::query()->delete();

        AuditLog::withReason('publishing per editor request', static function () use ($post): void {
            $post->update(['status' => 'published']);
        });

        $record = AuditRecordModel::query()->where('event', 'updated')->firstOrFail();
        $this->assertSame('publishing per editor request', $record->reason);
    }

    public function test_with_reason_returns_the_callback_result(): void
    {
        $this->assertSame(42, AuditLog::withReason('x', static fn (): int => 42));
    }

    public function test_a_change_without_a_reason_stores_null(): void
    {
        AuditLog::record('App\\Models\\Order', 9, 'updated', ['status' => 'a'], ['status' => 'b']);

        $record = AuditRecordModel::query()->where('auditable_id', '9')->firstOrFail();
        $this->assertNull($record->reason);
    }

    public function test_the_reason_scope_is_cleared_after_the_callback(): void
    {
        AuditLog::withReason('scoped', static fn () => null);

        AuditLog::record('App\\Models\\Order', 2, 'updated', ['status' => 'a'], ['status' => 'b']);

        $record = AuditRecordModel::query()->where('auditable_id', '2')->firstOrFail();
        $this->assertNull($record->reason);
    }
}
