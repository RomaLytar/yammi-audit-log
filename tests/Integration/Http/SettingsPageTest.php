<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Tests\TestCase;

final class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_settings_hub_shows_the_three_blocks(): void
    {
        $response = $this->get('audit-log/settings');

        $response->assertOk();
        $response->assertSee('General Settings');
        $response->assertSee('Database Connection');
        $response->assertSee('Facade Playground');
        $response->assertSee('Configure');
        $response->assertSee('audit-log/settings/general');
        $response->assertSee('audit-log/settings/database');
        $response->assertSee('audit-log/settings/playground');
    }

    public function test_the_database_block_reports_the_dedicated_connection_state(): void
    {
        $this->get('audit-log/settings')->assertSee('Disabled');

        $this->app['config']->set('audit-log.database.connection', 'audit_target');

        $this->get('audit-log/settings')->assertDontSee('Disabled');
    }

    public function test_the_nav_links_to_the_settings_page(): void
    {
        $this->get('audit-log')->assertSee('audit-log/settings');
    }
}
