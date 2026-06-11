<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Queue;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\Support\Models\User;
use Yammi\AuditLog\Tests\TestCase;

final class AsyncWriteTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.write.async', true);
        $app['config']->set('queue.default', 'database');
        $app['config']->set('queue.connections.database', [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('jobs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
    }

    public function test_the_insert_is_deferred_to_the_queue(): void
    {
        Post::create(['title' => 'Hello', 'status' => 'draft']);

        $this->assertSame(0, AuditRecordModel::query()->count());
        $this->assertSame(1, DB::table('jobs')->count());

        $this->artisan('queue:work', ['--once' => true, '--sleep' => 0])->assertSuccessful();

        $this->assertSame(1, AuditRecordModel::query()->count());
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_the_actor_is_resolved_at_capture_time_not_in_the_worker(): void
    {
        $this->actingAs(new User(['id' => 1, 'name' => 'Jane Doe']));

        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);

        $this->app['auth']->forgetGuards();

        $this->artisan('queue:work', ['--once' => true, '--sleep' => 0])->assertSuccessful();

        $timeline = $this->app->make(AuditRecordRepository::class)->timelineFor(
            AuditableReference::to($post->getMorphClass(), (string) $post->getKey()),
        );

        $this->assertCount(1, $timeline);
        $this->assertSame(ActorType::User, $timeline[0]->actor()->type);
        $this->assertSame('Jane Doe', $timeline[0]->actor()->displayLabel());
    }

    public function test_a_dedicated_queue_name_is_honoured(): void
    {
        $this->app['config']->set('audit-log.write.queue', 'audit');

        Post::create(['title' => 'Hello', 'status' => 'draft']);

        $this->assertSame('audit', DB::table('jobs')->value('queue'));
    }
}
