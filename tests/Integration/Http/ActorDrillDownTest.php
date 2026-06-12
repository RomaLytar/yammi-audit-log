<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\Support\Models\User;
use Yammi\AuditLog\Tests\TestCase;

final class ActorDrillDownTest extends TestCase
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

    public function test_the_actor_badge_links_to_the_filtered_feed(): void
    {
        $this->actingAs(new User(['id' => 1, 'name' => 'Jane Doe']));

        Post::create(['title' => 'Hello', 'status' => 'draft']);

        $response = $this->get('audit-log');

        $response->assertOk();
        $response->assertSee('actor_type=user', false);
        $response->assertSee('actor=Jane%20Doe', false);
    }

    public function test_the_actor_link_actually_filters(): void
    {
        $this->actingAs(new User(['id' => 1, 'name' => 'Jane Doe']));
        Post::create(['title' => 'Hello', 'status' => 'draft']);

        $this->app['auth']->forgetGuards();
        Post::create(['title' => 'System made', 'status' => 'draft']);

        $this->get('audit-log?actor_type=user&actor=Jane%20Doe')
            ->assertOk()
            ->assertSee('1 records');
    }
}
