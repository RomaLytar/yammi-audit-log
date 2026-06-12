<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Actor;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\Support\Models\User;
use Yammi\AuditLog\Tests\TestCase;

final class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
    }

    protected function defineWebRoutes($router): void
    {
        $router->get('impersonated-post', function () {
            Post::create(['title' => 'Hello', 'status' => 'draft']);

            return 'ok';
        })->middleware('web');
    }

    public function test_an_impersonated_change_names_both_people(): void
    {
        User::create(['id' => 9, 'name' => 'Support Admin']);
        $victim = User::create(['id' => 5, 'name' => 'Jane Doe']);

        $this->actingAs($victim)
            ->withSession(['impersonated_by' => 9])
            ->get('impersonated-post')
            ->assertOk();

        $actor = $this->latestActor();

        $this->assertSame(ActorType::User, $actor->type);
        $this->assertSame('5', $actor->identifier);
        $this->assertSame('Jane Doe (impersonated by Support Admin)', $actor->displayLabel());
    }

    public function test_a_plain_session_keeps_the_plain_label(): void
    {
        $user = User::create(['id' => 5, 'name' => 'Jane Doe']);

        $this->actingAs($user)
            ->get('impersonated-post')
            ->assertOk();

        $this->assertSame('Jane Doe', $this->latestActor()->displayLabel());
    }

    public function test_impersonating_yourself_is_not_flagged(): void
    {
        $user = User::create(['id' => 5, 'name' => 'Jane Doe']);

        $this->actingAs($user)
            ->withSession(['impersonated_by' => 5])
            ->get('impersonated-post')
            ->assertOk();

        $this->assertSame('Jane Doe', $this->latestActor()->displayLabel());
    }

    public function test_an_unknown_impersonator_falls_back_to_the_id(): void
    {
        $victim = User::create(['id' => 5, 'name' => 'Jane Doe']);

        $this->actingAs($victim)
            ->withSession(['impersonated_by' => 404])
            ->get('impersonated-post')
            ->assertOk();

        $this->assertSame('Jane Doe (impersonated by #404)', $this->latestActor()->displayLabel());
    }

    private function latestActor(): Actor
    {
        $records = $this->app->make(AuditRecordRepository::class)->timelineFor(
            AuditableReference::to(Post::class, '1'),
        );

        $this->assertNotSame([], $records);
        $this->assertInstanceOf(AuditRecord::class, $records[0]);

        return $records[0]->actor();
    }
}
