<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Queue;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;
use Yammi\AuditLog\Tests\Support\Jobs\PublishPostJob;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\Support\Models\User;
use Yammi\AuditLog\Tests\TestCase;

final class DatabaseQueuePropagationTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

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

    public function test_the_origin_and_correlation_are_serialized_into_the_payload(): void
    {
        $this->actingAs(new User(['id' => 9, 'name' => 'Jane Doe']));

        $correlation = $this->app->make(CorrelationContext::class);
        $correlation->push('5b29f077-90d2-46a3-9d97-2bbb7f7202df');
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        PublishPostJob::dispatch($post->getKey());
        $correlation->pop();

        $payload = (string) DB::table('jobs')->value('payload');

        $this->assertStringContainsString('audit_origin', $payload);
        $this->assertStringContainsString('Jane Doe', $payload);
        $this->assertStringContainsString('audit_correlation', $payload);
        $this->assertStringContainsString('5b29f077-90d2-46a3-9d97-2bbb7f7202df', $payload);
    }

    public function test_a_user_origin_survives_a_real_database_worker(): void
    {
        $this->actingAs(new User(['id' => 9, 'name' => 'Jane Doe']));

        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        PublishPostJob::dispatch($post->getKey());

        $this->app['auth']->forgetGuards();

        $this->artisan('queue:work', ['--once' => true, '--sleep' => 0])->assertSuccessful();

        $update = $this->latestRecordFor($post);

        $this->assertSame(ActorType::Job, $update->actor()->type);
        $this->assertStringContainsString('PublishPostJob', $update->actor()->displayLabel());
        $this->assertSame(ActorType::User, $update->origin()?->type);
        $this->assertSame('Jane Doe', $update->origin()?->displayLabel());
    }

    public function test_a_command_origin_survives_a_real_database_worker(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);

        Event::dispatch(new CommandStarting('app:import', new ArrayInput([]), new NullOutput));
        PublishPostJob::dispatch($post->getKey());
        Event::dispatch(new CommandFinished('app:import', new ArrayInput([]), new NullOutput, 0));

        $this->artisan('queue:work', ['--once' => true, '--sleep' => 0])->assertSuccessful();

        $update = $this->latestRecordFor($post);

        $this->assertSame(ActorType::Command, $update->origin()?->type);
        $this->assertSame('app:import', $update->origin()?->displayLabel());
    }

    public function test_the_correlation_from_dispatch_time_is_kept_by_the_worker(): void
    {
        $correlation = $this->app->make(CorrelationContext::class);
        $correlation->push('9c1f43dd-6f08-44ab-9542-0a1a02b9a8e4');
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        PublishPostJob::dispatch($post->getKey());
        $correlation->pop();

        $this->artisan('queue:work', ['--once' => true, '--sleep' => 0])->assertSuccessful();

        $update = $this->latestRecordFor($post);

        $this->assertSame('9c1f43dd-6f08-44ab-9542-0a1a02b9a8e4', $update->correlationId());
    }

    private function latestRecordFor(Post $post): AuditRecord
    {
        $timeline = $this->app->make(AuditRecordRepository::class)->timelineFor(
            AuditableReference::to($post->getMorphClass(), (string) $post->getKey()),
        );

        $this->assertNotSame([], $timeline, 'Expected the worker to have recorded the update.');

        return $timeline[0];
    }
}
