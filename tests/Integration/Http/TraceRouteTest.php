<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;
use Yammi\AuditLog\Tests\Support\Jobs\PublishPostJob;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class TraceRouteTest extends TestCase
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

    public function test_it_renders_a_change_chain(): void
    {
        $correlation = $this->app->make(CorrelationContext::class);
        $correlation->push('trace-abc');
        $post = Post::create(['title' => 'Order', 'status' => 'draft']);
        PublishPostJob::dispatchSync($post->getKey());
        $correlation->pop();

        $response = $this->get('audit-log/trace/trace-abc');

        $response->assertOk();
        $response->assertSee('Change chain');
        $response->assertSee('Root');
        $response->assertSee('Post');
    }

    public function test_an_unknown_correlation_returns_404(): void
    {
        $this->get('audit-log/trace/does-not-exist')->assertNotFound();
    }
}
