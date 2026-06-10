<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Yammi\AuditLog\Tests\Support\Models\User;
use Yammi\AuditLog\Tests\TestCase;

final class GateTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('audit-log.ui.middleware', ['web', 'auth']);
        $app['config']->set('audit-log.ui.gate', 'viewAuditLog');
    }

    protected function defineWebRoutes($router): void
    {
        $router->get('login', fn () => 'login')->name('login');
    }

    public function test_a_user_without_the_ability_is_forbidden(): void
    {
        Gate::define('viewAuditLog', static fn ($user): bool => false);
        $this->actingAs(new User(['id' => 1, 'name' => 'Guest']));

        $this->get('audit-log')->assertForbidden();
    }

    public function test_a_user_with_the_ability_can_view_the_dashboard(): void
    {
        Gate::define('viewAuditLog', static fn ($user): bool => true);
        $this->actingAs(new User(['id' => 1, 'name' => 'Admin']));

        $this->get('audit-log')->assertOk();
    }
}
