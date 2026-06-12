<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Infrastructure\Settings\Persistence\Eloquent\SettingModel;
use Yammi\AuditLog\Tests\TestCase;

final class SettingsUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('audit-log.ui.middleware', ['web']);
    }

    public function test_saving_persists_the_settings_and_updates_config(): void
    {
        $response = $this->post('audit-log/settings/general', $this->payload([
            'retention_days' => 90,
            'prune_schedule_enabled' => '0',
        ]));

        $response->assertRedirect(route('audit-log.settings.general'));
        $response->assertSessionHas('audit_log_status');

        $this->assertSame('90', SettingModel::query()->where('key', 'retention_days')->value('value'));
        $this->assertSame(90, $this->app['config']->get('audit-log.retention.days'));
        $this->assertFalse($this->app['config']->get('audit-log.retention.schedule.enabled'));
    }

    public function test_csv_settings_become_arrays_in_config(): void
    {
        $this->post('audit-log/settings/general', $this->payload([
            'redaction_keys' => 'password, token, iban',
            'ignore_attributes' => 'created_at',
        ]))->assertSessionHas('audit_log_status');

        $this->assertSame(['password', 'token', 'iban'], $this->app['config']->get('audit-log.redaction.keys'));
        $this->assertSame(['created_at'], $this->app['config']->get('audit-log.capture.ignore_attributes'));
    }

    public function test_string_settings_overlay_config(): void
    {
        $this->post('audit-log/settings/general', $this->payload([
            'jobs_monitor_url' => '/jobs-monitor',
            'write_async' => '1',
            'write_queue' => 'audit',
        ]))->assertSessionHas('audit_log_status');

        $this->assertSame('/jobs-monitor', $this->app['config']->get('audit-log.integrations.jobs_monitor.url'));
        $this->assertTrue($this->app['config']->get('audit-log.write.async'));
        $this->assertSame('audit', $this->app['config']->get('audit-log.write.queue'));
    }

    public function test_the_saved_value_survives_for_the_next_request(): void
    {
        $this->post('audit-log/settings/general', $this->payload(['retention_days' => 365]));

        $this->get('audit-log/settings/general')->assertSee('value="365"', false);
    }

    public function test_out_of_bounds_values_are_rejected(): void
    {
        $this->from('audit-log/settings/general')
            ->post('audit-log/settings/general', $this->payload(['retention_days' => 5]))
            ->assertSessionHasErrors('retention_days');

        $this->from('audit-log/settings/general')
            ->post('audit-log/settings/general', $this->payload(['retention_days' => 10000]))
            ->assertSessionHasErrors('retention_days');

        $this->from('audit-log/settings/general')
            ->post('audit-log/settings/general', $this->payload(['ui_throttle' => 'not-a-throttle']))
            ->assertSessionHasErrors('ui_throttle');

        $this->from('audit-log/settings/general')
            ->post('audit-log/settings/general', $this->payload(['prune_cron' => 'rm -rf /']))
            ->assertSessionHasErrors('prune_cron');

        $this->assertSame(0, SettingModel::query()->count());
    }

    public function test_reset_clears_the_stored_settings(): void
    {
        $this->post('audit-log/settings/general', $this->payload(['retention_days' => 90]));
        $this->assertGreaterThan(0, SettingModel::query()->count());

        $this->post('audit-log/settings/general/reset')
            ->assertRedirect(route('audit-log.settings.general'));

        $this->assertSame(0, SettingModel::query()->count());
    }

    /**
     * @param  array<string, int|string>  $overrides
     * @return array<string, int|string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'enabled' => '1',
            'retention_days' => 180,
            'prune_schedule_enabled' => '1',
            'prune_cron' => '0 3 * * *',
            'write_async' => '0',
            'write_queue' => '',
            'integrity_enabled' => '0',
            'ignore_attributes' => 'created_at, updated_at',
            'request_context' => '0',
            'redaction_keys' => 'password, token',
            'redaction_placeholder' => '[redacted]',
            'timezone' => '',
            'ui_enabled' => '1',
            'ui_throttle' => '60,1',
            'jobs_monitor_url' => '',
        ], $overrides);
    }
}
