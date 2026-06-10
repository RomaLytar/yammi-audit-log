<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class DashboardRouteTest extends TestCase
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

    public function test_the_dashboard_renders_recorded_changes(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $response = $this->get('audit-log');

        $response->assertOk();
        $response->assertSee('Change history');
        $response->assertSee('Post');
        $response->assertSee('published');
    }

    public function test_the_dashboard_shows_an_empty_state(): void
    {
        $response = $this->get('audit-log');

        $response->assertOk();
        $response->assertSee('No changes recorded yet');
    }

    public function test_it_filters_by_event(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $this->get('audit-log?event=updated')->assertOk()->assertSee('published');
        $this->get('audit-log?event=deleted')->assertOk()->assertSee('No changes match these filters');
    }

    public function test_it_filters_by_actor_type(): void
    {
        Post::create(['title' => 'Hello', 'status' => 'draft']);

        $this->get('audit-log?actor_type=user')->assertSee('No changes match these filters');
        $this->get('audit-log?actor_type=system')->assertOk()->assertSee('Post');
    }
}
