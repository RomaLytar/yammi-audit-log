<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Yammi\AuditLog\Tests\TestCase;

final class UiMiddlewareFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.ui.middleware', 'not-an-array');
    }

    public function test_a_malformed_ui_middleware_config_falls_back_to_an_authenticated_stack(): void
    {
        $route = $this->app->make(Router::class)->getRoutes()->getByName('audit-log.dashboard');

        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
    }
}
