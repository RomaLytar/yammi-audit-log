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

    public function test_saving_persists_the_setting_and_updates_config(): void
    {
        $response = $this->post('audit-log/settings', [
            'retention_days' => 90,
            'prune_schedule_enabled' => '0',
        ]);

        $response->assertRedirect(route('audit-log.settings'));
        $response->assertSessionHas('audit_log_status');

        $this->assertSame('90', SettingModel::query()->where('key', 'retention_days')->value('value'));
        $this->assertSame(90, $this->app['config']->get('audit-log.retention.days'));
        $this->assertFalse($this->app['config']->get('audit-log.retention.schedule.enabled'));
    }

    public function test_the_saved_value_survives_for_the_next_request(): void
    {
        $this->post('audit-log/settings', ['retention_days' => 365]);

        $this->get('audit-log/settings')->assertSee('value="365"', false);
    }

    public function test_out_of_bounds_values_are_rejected(): void
    {
        $this->from('audit-log/settings')
            ->post('audit-log/settings', ['retention_days' => 5])
            ->assertSessionHasErrors('retention_days');

        $this->from('audit-log/settings')
            ->post('audit-log/settings', ['retention_days' => 10000])
            ->assertSessionHasErrors('retention_days');

        $this->assertSame(0, SettingModel::query()->count());
    }

    public function test_reset_clears_the_stored_settings(): void
    {
        $this->post('audit-log/settings', ['retention_days' => 90]);
        $this->assertSame(2, SettingModel::query()->count());

        $this->post('audit-log/settings/reset')
            ->assertRedirect(route('audit-log.settings'));

        $this->assertSame(0, SettingModel::query()->count());
    }
}
