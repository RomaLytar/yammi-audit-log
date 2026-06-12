<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Tenancy;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\FixedTenantResolver;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.tenancy.resolver', FixedTenantResolver::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        FixedTenantResolver::$tenant = null;
    }

    protected function tearDown(): void
    {
        FixedTenantResolver::$tenant = null;

        parent::tearDown();
    }

    public function test_new_records_are_stamped_with_the_current_tenant(): void
    {
        FixedTenantResolver::$tenant = 'acme';

        Post::create(['title' => 'Hello', 'status' => 'draft']);

        $row = AuditRecordModel::query()->withoutGlobalScopes()->firstOrFail();

        $this->assertSame('acme', $row->getAttribute('tenant_id'));
    }

    public function test_reads_are_scoped_to_the_current_tenant(): void
    {
        FixedTenantResolver::$tenant = 'acme';
        Post::create(['title' => 'Acme post', 'status' => 'draft']);

        FixedTenantResolver::$tenant = 'globex';
        Post::create(['title' => 'Globex post', 'status' => 'draft']);

        $this->assertSame(1, AuditLog::changes()->total);
        $this->assertSame('2', AuditLog::changes()->entries[0]->auditableId);

        FixedTenantResolver::$tenant = 'acme';
        $this->assertSame('1', AuditLog::changes()->entries[0]->auditableId);

        $timeline = AuditLog::for(Post::class, 2);
        $this->assertTrue($timeline->isEmpty());
    }

    public function test_without_a_tenant_everything_is_visible(): void
    {
        FixedTenantResolver::$tenant = 'acme';
        Post::create(['title' => 'Acme post', 'status' => 'draft']);

        FixedTenantResolver::$tenant = 'globex';
        Post::create(['title' => 'Globex post', 'status' => 'draft']);

        FixedTenantResolver::$tenant = null;

        $this->assertSame(2, AuditLog::changes()->total);
    }

    public function test_the_dashboard_is_scoped_too(): void
    {
        FixedTenantResolver::$tenant = 'acme';
        Post::create(['title' => 'acme-marker', 'status' => 'draft']);

        FixedTenantResolver::$tenant = 'globex';
        Post::create(['title' => 'globex-marker', 'status' => 'draft']);

        $this->get('audit-log')
            ->assertOk()
            ->assertSee('globex-marker')
            ->assertDontSee('acme-marker');
    }
}
