<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Infrastructure\Settings\Persistence\Eloquent\SettingModel;
use Yammi\AuditLog\Infrastructure\Settings\StoredSettingsApplier;
use Yammi\AuditLog\Tests\TestCase;

final class ToggleUiCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_enable_stores_the_toggle(): void
    {
        $this->artisan('audit-log:ui', ['state' => 'enable'])->assertSuccessful();

        $this->assertSame('1', SettingModel::query()->where('key', 'ui_enabled')->value('value'));
    }

    public function test_disable_stores_the_toggle(): void
    {
        $this->artisan('audit-log:ui', ['state' => 'disable'])->assertSuccessful();

        $this->assertSame('0', SettingModel::query()->where('key', 'ui_enabled')->value('value'));
    }

    public function test_the_stored_toggle_overlays_config_at_boot(): void
    {
        $this->artisan('audit-log:ui', ['state' => 'enable'])->assertSuccessful();

        $this->app['config']->set('audit-log.ui.enabled', false);
        $this->app->make(StoredSettingsApplier::class)->apply();

        $this->assertTrue($this->app['config']->get('audit-log.ui.enabled'));
    }

    public function test_an_unknown_state_fails(): void
    {
        $this->artisan('audit-log:ui', ['state' => 'maybe'])->assertFailed();
    }
}
