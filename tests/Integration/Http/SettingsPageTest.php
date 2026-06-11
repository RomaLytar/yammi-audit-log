<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Tests\TestCase;

final class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_settings_page_renders_with_the_defaults(): void
    {
        $response = $this->get('audit-log/settings');

        $response->assertOk();
        $response->assertSee('Settings');
        $response->assertSee('Data retention (days)');
        $response->assertSee('value="180"', false);
        $response->assertSee('Audit database');
        $response->assertSee('Application default');
    }

    public function test_the_nav_links_to_the_settings_page(): void
    {
        $this->get('audit-log')->assertSee('audit-log/settings');
    }

    public function test_the_dedicated_card_reflects_a_configured_connection(): void
    {
        $this->app['config']->set('database.connections.audit_target', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $this->app['config']->set('audit-log.database.connection', 'audit_target');

        $response = $this->get('audit-log/settings');

        $response->assertOk();
        $response->assertSee('audit_target');
        $response->assertSee('in use');
    }
}
