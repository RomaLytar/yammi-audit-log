<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Tests\Support\Models\User;
use Yammi\AuditLog\Tests\TestCase;

final class DashboardAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.ui.middleware', ['web', 'auth']);
    }

    protected function defineWebRoutes($router): void
    {
        $router->get('login', fn () => 'login page')->name('login');
    }

    public function test_guests_are_redirected_away_from_the_dashboard(): void
    {
        $this->get('audit-log')->assertStatus(302);
    }

    public function test_authenticated_users_can_open_the_dashboard(): void
    {
        $this->actingAs(new User(['id' => 1, 'name' => 'Admin']));

        $this->get('audit-log')->assertOk();
    }
}
