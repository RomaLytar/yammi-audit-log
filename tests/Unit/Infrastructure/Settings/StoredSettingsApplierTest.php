<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Settings;

use Illuminate\Config\Repository as ConfigRepository;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Domain\Settings\Repository\GeneralSettingRepository;
use Yammi\AuditLog\Infrastructure\Settings\StoredSettingsApplier;
use Yammi\AuditLog\Tests\Support\InMemoryGeneralSettingRepository;

final class StoredSettingsApplierTest extends TestCase
{
    public function test_stored_values_overlay_config(): void
    {
        $repository = new InMemoryGeneralSettingRepository;
        $repository->stored = ['general' => ['retention_days' => '30', 'prune_schedule_enabled' => '0']];

        $config = new ConfigRepository(['audit-log' => ['retention' => ['days' => 180, 'schedule' => ['enabled' => true]]]]);

        (new StoredSettingsApplier(new SettingRegistry, $repository, $config))->apply();

        $this->assertSame(30, $config->get('audit-log.retention.days'));
        $this->assertFalse($config->get('audit-log.retention.schedule.enabled'));
    }

    public function test_stored_values_are_clamped_when_applied(): void
    {
        $repository = new InMemoryGeneralSettingRepository;
        $repository->stored = ['general' => ['retention_days' => '99999']];

        $config = new ConfigRepository;

        (new StoredSettingsApplier(new SettingRegistry, $repository, $config))->apply();

        $this->assertSame(9999, $config->get('audit-log.retention.days'));
    }

    public function test_config_is_untouched_without_stored_values(): void
    {
        $config = new ConfigRepository(['audit-log' => ['retention' => ['days' => 180]]]);

        (new StoredSettingsApplier(new SettingRegistry, new InMemoryGeneralSettingRepository, $config))->apply();

        $this->assertSame(180, $config->get('audit-log.retention.days'));
    }

    public function test_an_unreachable_settings_store_is_ignored(): void
    {
        $broken = new class implements GeneralSettingRepository
        {
            public function all(): array
            {
                throw new RuntimeException('no table yet');
            }

            public function get(string $group, string $key): ?string
            {
                return null;
            }

            public function set(string $group, string $key, string $value, string $type): void {}

            public function remove(string $group, string $key): void {}
        };

        $config = new ConfigRepository(['audit-log' => ['retention' => ['days' => 180]]]);

        (new StoredSettingsApplier(new SettingRegistry, $broken, $config))->apply();

        $this->assertSame(180, $config->get('audit-log.retention.days'));
    }
}
