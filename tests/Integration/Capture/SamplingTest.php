<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Capture;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class SamplingTest extends TestCase
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

    public function test_a_zero_sample_rate_drops_capture(): void
    {
        AuditLog::policy(Post::class)->sample(0.0);

        Post::create(['title' => 'A', 'status' => 'draft']);

        $this->assertSame(0, AuditRecordModel::query()->count());
    }

    public function test_capture_is_unchanged_without_sampling(): void
    {
        Post::create(['title' => 'A', 'status' => 'draft']);

        $this->assertSame(1, AuditRecordModel::query()->count());
    }
}
