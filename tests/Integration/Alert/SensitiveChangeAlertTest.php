<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Alert;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Events\SensitiveChangeRecorded;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class SensitiveChangeAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.alerts.rules', [
            ['model' => Post::class, 'attributes' => ['status'], 'events' => ['updated']],
            ['model' => 'App\\Models\\Order'],
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
    }

    public function test_a_matching_change_fires_the_event(): void
    {
        Event::fake([SensitiveChangeRecorded::class]);

        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        Event::assertDispatchedTimes(SensitiveChangeRecorded::class, 1);
        Event::assertDispatched(SensitiveChangeRecorded::class, function (SensitiveChangeRecorded $event): bool {
            return $event->entry->event === 'updated'
                && array_key_exists('status', $event->entry->changes)
                && ($event->rule['attributes'] ?? null) === ['status'];
        });
    }

    public function test_non_matching_changes_stay_silent(): void
    {
        Event::fake([SensitiveChangeRecorded::class]);

        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['title' => 'Renamed']);

        Event::assertNotDispatched(SensitiveChangeRecorded::class);
    }

    public function test_manual_records_are_inspected_too(): void
    {
        Event::fake([SensitiveChangeRecorded::class]);

        AuditLog::record('App\\Models\\Order', 7, 'deleted', ['status' => 'paid'], []);

        Event::assertDispatchedTimes(SensitiveChangeRecorded::class, 1);
    }
}
