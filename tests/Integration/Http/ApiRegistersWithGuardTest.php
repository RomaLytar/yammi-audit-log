<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Tests\TestCase;

final class ApiRegistersWithGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.api.enabled', true);
        $app['config']->set('audit-log.api.middleware', ['api', 'auth']);
    }

    public function test_an_auth_guard_in_the_middleware_registers_the_routes(): void
    {
        $this->assertTrue($this->app->make('router')->has('audit-log.api.changes'));
    }
}
