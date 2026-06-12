<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Tenancy;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Tests\Support\FixedTenantResolver;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class TenantIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.tenancy.resolver', FixedTenantResolver::class);
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

    protected function tearDown(): void
    {
        FixedTenantResolver::$tenant = null;

        parent::tearDown();
    }

    public function test_the_hash_chain_stays_unbroken_across_tenants(): void
    {
        FixedTenantResolver::$tenant = 'acme';
        Post::create(['title' => 'Acme post', 'status' => 'draft']);

        FixedTenantResolver::$tenant = 'globex';
        Post::create(['title' => 'Globex post', 'status' => 'draft']);

        FixedTenantResolver::$tenant = 'acme';
        Post::create(['title' => 'Second acme post', 'status' => 'draft']);

        FixedTenantResolver::$tenant = 'initech';

        $this->artisan('audit-log:verify')
            ->expectsOutputToContain('3 hashed record(s) verified')
            ->assertSuccessful();
    }
}
