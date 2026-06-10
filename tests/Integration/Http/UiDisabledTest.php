<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Tests\TestCase;

final class UiDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.ui.enabled', false);
    }

    public function test_the_dashboard_routes_are_not_registered(): void
    {
        $this->get('audit-log')->assertNotFound();
        $this->get('audit-log/noise')->assertNotFound();
    }
}
