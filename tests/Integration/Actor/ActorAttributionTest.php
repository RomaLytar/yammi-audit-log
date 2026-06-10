<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Actor;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Tests\Support\Jobs\PublishPostJob;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\Support\Models\User;
use Yammi\AuditLog\Tests\TestCase;

final class ActorAttributionTest extends TestCase
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

    public function test_an_authenticated_user_is_attributed_as_the_actor(): void
    {
        $this->actingAs(new User(['id' => 1, 'name' => 'Jane Doe']));

        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $actor = $this->timelineFor($post)[0]->actor();

        $this->assertSame(ActorType::User, $actor->type);
        $this->assertSame('Jane Doe', $actor->displayLabel());
    }

    public function test_a_job_dispatched_by_a_user_keeps_the_user_as_origin(): void
    {
        $this->actingAs(new User(['id' => 9, 'name' => 'Jane Doe']));

        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);

        PublishPostJob::dispatchSync($post->getKey());

        $timeline = $this->timelineFor($post);

        $this->assertSame(ActorType::Job, $timeline[0]->actor()->type);
        $this->assertStringContainsString('PublishPostJob', $timeline[0]->actor()->displayLabel());
        $this->assertSame('Jane Doe', $timeline[0]->origin()?->displayLabel());

        $this->assertSame(ActorType::User, $timeline[1]->actor()->type);
        $this->assertNull($timeline[1]->origin());
    }

    public function test_a_queued_job_is_attributed_as_the_actor(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);

        PublishPostJob::dispatchSync($post->getKey());

        $timeline = $this->timelineFor($post);

        $this->assertSame(ActorType::Job, $timeline[0]->actor()->type);
        $this->assertStringContainsString('PublishPostJob', $timeline[0]->actor()->displayLabel());
        $this->assertSame(ActorType::System, $timeline[1]->actor()->type);
    }

    /**
     * @return array<int, AuditRecord>
     */
    private function timelineFor(Post $post): array
    {
        return $this->app->make(AuditRecordRepository::class)->timelineFor(
            AuditableReference::to($post->getMorphClass(), (string) $post->getKey()),
        );
    }
}
