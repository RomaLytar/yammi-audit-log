<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Label;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Tests\Support\Models\Customer;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\Support\Models\User;
use Yammi\AuditLog\Tests\TestCase;

final class ConventionLabelResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.labels.map', [
            'user_id' => User::class,
            'customer_id' => Customer::class,
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
        });

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
        });
    }

    public function test_an_update_snapshots_labels_for_old_and_new_values(): void
    {
        User::create(['name' => 'John Doe']);
        User::create(['name' => 'Jane Smith']);
        $post = Post::create(['title' => 'Hello', 'status' => 'draft', 'user_id' => 1]);

        $post->update(['user_id' => 2]);

        $labels = $this->latestRecordFor($post)->labels();

        $this->assertSame('John Doe', $labels->for('user_id.old'));
        $this->assertSame('Jane Smith', $labels->for('user_id.new'));
    }

    public function test_the_get_audit_label_convention_wins_over_attribute_fallback(): void
    {
        Customer::create(['code' => 'C-42']);
        $post = Post::create(['title' => 'Hello', 'status' => 'draft', 'customer_id' => 1]);

        $labels = $this->latestRecordFor($post)->labels();

        $this->assertSame('Customer #C-42', $labels->for('customer_id.new'));
    }

    public function test_a_missing_referenced_row_yields_no_label(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft', 'user_id' => 999]);

        $this->assertTrue($this->latestRecordFor($post)->labels()->isEmpty());
    }

    public function test_unrelated_changes_trigger_no_label_lookup(): void
    {
        User::create(['name' => 'John Doe']);
        $post = Post::create(['title' => 'Hello', 'status' => 'draft', 'user_id' => 1]);

        $post->update(['status' => 'published']);

        $this->assertTrue($this->latestRecordFor($post)->labels()->isEmpty());
    }

    public function test_the_dashboard_shows_the_label_next_to_the_raw_value(): void
    {
        User::create(['name' => 'John Doe']);
        User::create(['name' => 'Jane Smith']);
        $post = Post::create(['title' => 'Hello', 'status' => 'draft', 'user_id' => 1]);
        $post->update(['user_id' => 2]);

        $response = $this->get('audit-log');

        $response->assertOk();
        $response->assertSee('John Doe');
        $response->assertSee('Jane Smith');
    }

    private function latestRecordFor(Post $post): AuditRecord
    {
        $timeline = $this->app->make(AuditRecordRepository::class)->timelineFor(
            AuditableReference::to($post->getMorphClass(), (string) $post->getKey()),
        );

        return $timeline[0];
    }
}
