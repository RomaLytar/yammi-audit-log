<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Actor;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class SchedulerAttributionTest extends TestCase
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

    public function test_a_change_made_by_a_scheduled_task_is_attributed_to_the_scheduler(): void
    {
        $this->app->make(Schedule::class)
            ->call(static function (): void {
                Post::create(['title' => 'Nightly', 'status' => 'draft']);
            })
            ->name('seed-nightly-post')
            ->everyMinute();

        $this->artisan('schedule:run')->assertSuccessful();

        $post = Post::query()->firstOrFail();
        $timeline = $this->app->make(AuditRecordRepository::class)->timelineFor(
            AuditableReference::to($post->getMorphClass(), (string) $post->getKey()),
        );

        $this->assertCount(1, $timeline);
        $this->assertSame(ActorType::Scheduler, $timeline[0]->actor()->type);
        $this->assertSame('seed-nightly-post', $timeline[0]->actor()->displayLabel());
    }

    public function test_changes_after_the_scheduled_task_fall_back_to_the_command_actor(): void
    {
        $this->app->make(Schedule::class)
            ->call(static fn () => null)
            ->name('noop')
            ->everyMinute();

        $this->artisan('schedule:run')->assertSuccessful();

        $post = Post::create(['title' => 'After', 'status' => 'draft']);

        $timeline = $this->app->make(AuditRecordRepository::class)->timelineFor(
            AuditableReference::to($post->getMorphClass(), (string) $post->getKey()),
        );

        $this->assertNotSame(ActorType::Scheduler, $timeline[0]->actor()->type);
    }
}
