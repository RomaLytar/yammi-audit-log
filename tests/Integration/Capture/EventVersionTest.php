<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Capture;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class EventVersionTest extends TestCase
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

    public function test_the_schema_version_is_stamped_on_write(): void
    {
        Post::create(['title' => 'A', 'status' => 'draft']);

        $this->assertSame(
            AuditRecord::SCHEMA_VERSION,
            (int) AuditRecordModel::query()->value('event_version'),
        );
    }

    public function test_the_version_is_carried_through_to_consumers(): void
    {
        $post = Post::create(['title' => 'A', 'status' => 'draft']);

        $entry = AuditLog::for(Post::class, (string) $post->getKey())->entries[0] ?? null;

        $this->assertNotNull($entry);
        $this->assertSame(AuditRecord::SCHEMA_VERSION, $entry->eventVersion);
    }
}
