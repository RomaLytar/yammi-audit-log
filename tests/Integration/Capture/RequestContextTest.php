<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Capture;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class RequestContextTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.capture.request_context', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
    }

    protected function defineWebRoutes($router): void
    {
        $router->get('make-post', function () {
            Post::create(['title' => 'Hello', 'status' => 'draft']);

            return 'ok';
        });
    }

    public function test_http_changes_carry_request_metadata(): void
    {
        $this->get('make-post?source=test')->assertOk();

        $context = $this->latestContext();

        $this->assertSame('127.0.0.1', $context['ip']);
        $this->assertStringContainsString('make-post', $context['url']);
        $this->assertSame('GET', $context['method']);
        $this->assertArrayHasKey('user_agent', $context);
    }

    public function test_console_changes_carry_no_request_metadata(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);

        $timeline = $this->app->make(AuditRecordRepository::class)->timelineFor(
            AuditableReference::to($post->getMorphClass(), (string) $post->getKey()),
        );

        $this->assertSame([], $timeline[0]->context());
    }

    public function test_the_dashboard_shows_the_request_metadata(): void
    {
        $this->get('make-post')->assertOk();

        $this->get('audit-log')->assertOk()->assertSee('127.0.0.1');
    }

    /**
     * @return array<string, string>
     */
    private function latestContext(): array
    {
        $post = Post::query()->firstOrFail();

        $timeline = $this->app->make(AuditRecordRepository::class)->timelineFor(
            AuditableReference::to($post->getMorphClass(), (string) $post->getKey()),
        );

        return $timeline[0]->context();
    }
}
