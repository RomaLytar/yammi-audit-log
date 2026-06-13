<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Integrity;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditDigestModel;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class SignedDigestTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.integrity.enabled', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
    }

    public function test_digest_records_the_chain_state_and_verify_passes(): void
    {
        Post::create(['title' => 'A', 'status' => 'draft']);
        Post::create(['title' => 'B', 'status' => 'draft']);

        $this->artisan('audit-log:digest')->assertSuccessful();

        $digest = AuditDigestModel::query()->firstOrFail();
        $this->assertSame(2, (int) $digest->getAttribute('record_count'));
        $this->assertNotNull($digest->getAttribute('chain_head'));

        $this->artisan('audit-log:verify')->assertSuccessful();
    }

    public function test_verify_catches_deletion_of_the_signed_chain_head(): void
    {
        Post::create(['title' => 'A', 'status' => 'draft']);
        Post::create(['title' => 'B', 'status' => 'draft']);

        $this->artisan('audit-log:digest')->assertSuccessful();

        $headId = AuditRecordModel::query()->orderByDesc('id')->value('id');
        AuditRecordModel::query()->whereKey($headId)->delete();

        $this->artisan('audit-log:verify')->assertFailed();
    }
}
