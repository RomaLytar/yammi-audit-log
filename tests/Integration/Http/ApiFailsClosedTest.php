<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Tests\TestCase;

final class ApiFailsClosedTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.api.enabled', true);
        $app['config']->set('audit-log.api.middleware', ['api']);
    }

    public function test_enabling_the_api_without_an_auth_guard_does_not_register_the_routes(): void
    {
        $this->assertFalse($this->app->make('router')->has('audit-log.api.changes'));

        $this->getJson('audit-log/api/changes')->assertNotFound();
        $this->getJson('audit-log/api/stats')->assertNotFound();
    }
}
