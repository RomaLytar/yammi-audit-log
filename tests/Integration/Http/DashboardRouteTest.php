<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
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

    public function test_timestamps_follow_the_display_timezone(): void
    {
        $this->app['config']->set('audit-log.timezone', 'Asia/Tokyo');

        $this->app->make(AuditRecordRepository::class)->save(
            new AuditRecord(
                auditable: AuditableReference::to('App\\Models\\Order', 1),
                event: ChangeType::Created,
                diff: Diff::between([], ['status' => 'new']),
                actor: Actor::system(),
                origin: null,
                labels: LabelSnapshot::empty(),
                occurredAt: (new \DateTimeImmutable('today'))->setTime(1, 0),
            ),
        );

        $this->get('audit-log')->assertOk()->assertSee('10:00');
    }

    public function test_it_searches_change_content(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $this->get('audit-log?q=published')->assertOk()->assertSee('1 records');
        $this->get('audit-log?q=refunded')->assertOk()->assertSee('No changes match these filters');
    }

    public function test_it_filters_by_actor_type(): void
    {
        Post::create(['title' => 'Hello', 'status' => 'draft']);

        $this->get('audit-log?actor_type=user')->assertSee('No changes match these filters');
        $this->get('audit-log?actor_type=system')->assertOk()->assertSee('Post');
    }
}
