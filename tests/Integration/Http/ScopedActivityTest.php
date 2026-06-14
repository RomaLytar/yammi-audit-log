<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class ScopedActivityTest extends TestCase
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

    public function test_a_signed_activity_url_renders_the_subject_feed(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $url = AuditLog::activityUrl(Post::class, $post->id, 30);

        $this->get($url)
            ->assertOk()
            ->assertSee('Account activity')
            ->assertSee('status');
    }

    public function test_an_unsigned_url_is_rejected(): void
    {
        $url = AuditLog::activityUrl(Post::class, 1, 30);

        $this->get((string) strtok($url, '?'))->assertForbidden();
    }
}
