<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Tests\TestCase;

final class SettingsGeneralPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_form_renders_every_section_with_defaults(): void
    {
        $response = $this->get('audit-log/settings/general');

        $response->assertOk();
        $response->assertSee('General settings');
        $response->assertSee('Data retention (days)');
        $response->assertSee('Redaction');
        $response->assertSee('Secret key patterns');
        $response->assertSee('Capture changes');
        $response->assertSee('Async writes');
        $response->assertSee('JobsMonitor URL');
        $response->assertSee('Observability');
        $response->assertSee('APM trace URL');
        $response->assertSee('Postman export');
    }

    public function test_the_timezone_field_shows_the_effective_zone(): void
    {
        $this->app['config']->set('app.timezone', 'UTC');

        $this->get('audit-log/settings/general')->assertSee('value="UTC"', false);
    }

    public function test_retention_and_cron_offer_presets_with_a_custom_entry(): void
    {
        $response = $this->get('audit-log/settings/general');

        $response->assertOk();
        $response->assertSee('180 days (default)');
        $response->assertSee('30 days');
        $response->assertSee('Daily at 03:00');
        $response->assertSee('Every 6 hours');
        $response->assertSee('Custom…');
    }

    public function test_a_custom_stored_value_is_shown_as_a_custom_option(): void
    {
        $this->app['config']->set('audit-log.retention.days', 45);

        $this->get('audit-log/settings/general')->assertSee('Custom: 45');
    }
}
