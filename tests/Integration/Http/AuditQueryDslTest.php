<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class AuditQueryDslTest extends TestCase
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

    public function test_the_dsl_runs_through_the_same_path_as_the_array_api(): void
    {
        $post = Post::create(['title' => 'A', 'status' => 'pending']);
        $post->update(['status' => 'paid']);

        $dsl = AuditLog::query()->field('status')->from('pending')->to('paid')->get();
        $array = AuditLog::changes(['field' => 'status', 'value_from' => 'pending', 'value_to' => 'paid']);

        $this->assertSame(1, $dsl->total);
        $this->assertSame($array->total, $dsl->total);
    }

    public function test_event_and_actor_type_filters(): void
    {
        $post = Post::create(['title' => 'A', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $this->assertSame(1, AuditLog::query()->event(ChangeType::Updated)->get()->total);
        $this->assertSame(2, AuditLog::query()->actorType('system')->get()->total);
    }
}
