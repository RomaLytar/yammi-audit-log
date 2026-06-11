<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Facade;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\Support\Models\User;
use Yammi\AuditLog\Tests\TestCase;

final class ManualRecordingTest extends TestCase
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

    public function test_a_manual_change_is_recorded_with_type_and_id(): void
    {
        $entry = AuditLog::record(
            'App\\Models\\Order',
            42,
            'updated',
            before: ['status' => 'paid'],
            after: ['status' => 'refunded'],
        );

        $this->assertNotNull($entry);
        $this->assertSame('App\\Models\\Order', $entry->auditableType);
        $this->assertSame('42', $entry->auditableId);
        $this->assertSame('updated', $entry->event);
        $this->assertSame(['status' => ['old' => 'paid', 'new' => 'refunded']], $entry->changes);

        $this->assertSame(1, AuditRecordModel::query()->count());
    }

    public function test_a_model_instance_can_be_passed_directly(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        AuditRecordModel::query()->delete();

        $entry = AuditLog::record($post, null, 'updated', ['status' => 'draft'], ['status' => 'published']);

        $this->assertNotNull($entry);
        $this->assertSame($post->getMorphClass(), $entry->auditableType);
        $this->assertSame((string) $post->getKey(), $entry->auditableId);
    }

    public function test_the_actor_is_attributed_like_any_captured_change(): void
    {
        $this->actingAs(new User(['id' => 1, 'name' => 'Jane Doe']));

        $entry = AuditLog::record('App\\Models\\Order', 1, 'deleted', ['status' => 'paid'], []);

        $this->assertNotNull($entry);
        $this->assertSame(ActorType::User->value, $entry->actorType);
        $this->assertSame('Jane Doe', $entry->actorLabel);
    }

    public function test_secrets_are_redacted_in_manual_records(): void
    {
        $entry = AuditLog::record(
            'App\\Models\\User',
            1,
            'updated',
            before: ['password' => 'old-secret'],
            after: ['password' => 'new-secret'],
        );

        $this->assertNotNull($entry);
        $this->assertSame(['password' => ['old' => '[redacted]', 'new' => '[redacted]']], $entry->changes);
    }

    public function test_a_no_op_manual_update_records_nothing(): void
    {
        $entry = AuditLog::record('App\\Models\\Order', 1, 'updated', ['status' => 'paid'], ['status' => 'paid']);

        $this->assertNull($entry);
        $this->assertSame(0, AuditRecordModel::query()->count());
    }

    public function test_an_unknown_event_is_rejected(): void
    {
        $this->expectException(InvalidAuditData::class);

        AuditLog::record('App\\Models\\Order', 1, 'exploded', [], []);
    }

    public function test_a_missing_id_is_rejected_for_class_strings(): void
    {
        $this->expectException(InvalidAuditData::class);

        AuditLog::record('App\\Models\\Order', null, 'updated', [], ['status' => 'x']);
    }
}
