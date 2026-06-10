<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Facade;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class AuditLogFacadeTest extends TestCase
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

    public function test_it_returns_the_timeline_for_a_model(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $timeline = AuditLog::for($post);

        $this->assertSame(2, $timeline->count());
        $this->assertSame('updated', $timeline->entries[0]->event);
        $this->assertSame('published', $timeline->entries[0]->changes['status']['new']);
        $this->assertSame('draft', $timeline->entries[0]->changes['status']['old']);
        $this->assertSame('created', $timeline->entries[1]->event);
    }

    public function test_it_returns_the_timeline_by_type_and_id(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);

        $timeline = AuditLog::for($post->getMorphClass(), $post->getKey());

        $this->assertSame(1, $timeline->count());
        $this->assertSame('created', $timeline->entries[0]->event);
    }
}
