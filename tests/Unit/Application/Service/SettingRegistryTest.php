<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Service;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

final class SettingRegistryTest extends TestCase
{
    public function test_retention_days_is_registered_with_its_bounds(): void
    {
        $definition = (new SettingRegistry)->find('retention_days');

        $this->assertNotNull($definition);
        $this->assertSame('audit-log.retention.days', $definition->configPath);
        $this->assertSame(SettingType::Integer, $definition->type);
        $this->assertSame(180, $definition->default);
        $this->assertSame(7, $definition->min);
        $this->assertSame(9999, $definition->max);
    }

    public function test_every_runtime_safe_config_is_registered(): void
    {
        $registry = new SettingRegistry;

        $expected = [
            'enabled' => 'audit-log.enabled',
            'retention_days' => 'audit-log.retention.days',
            'prune_schedule_enabled' => 'audit-log.retention.schedule.enabled',
            'prune_cron' => 'audit-log.retention.schedule.cron',
            'write_async' => 'audit-log.write.async',
            'write_queue' => 'audit-log.write.queue',
            'ignore_attributes' => 'audit-log.capture.ignore_attributes',
            'request_context' => 'audit-log.capture.request_context',
            'redaction_keys' => 'audit-log.redaction.keys',
            'redaction_placeholder' => 'audit-log.redaction.placeholder',
            'timezone' => 'audit-log.timezone',
            'ui_enabled' => 'audit-log.ui.enabled',
            'ui_throttle' => 'audit-log.ui.throttle',
            'jobs_monitor_url' => 'audit-log.integrations.jobs_monitor.url',
        ];

        $actual = [];

        foreach ($registry->all() as $definition) {
            $actual[$definition->key] = $definition->configPath;
        }

        $this->assertSame($expected, $actual);
    }

    public function test_bootstrap_critical_configs_stay_out_of_the_registry(): void
    {
        $registry = new SettingRegistry;

        foreach ($registry->all() as $definition) {
            $this->assertStringNotContainsString('database.connection', $definition->configPath);
            $this->assertStringNotContainsString('ui.path', $definition->configPath);
            $this->assertStringNotContainsString('ui.middleware', $definition->configPath);
            $this->assertStringNotContainsString('ui.gate', $definition->configPath);
        }
    }

    public function test_grouped_buckets_definitions_by_group(): void
    {
        $grouped = (new SettingRegistry)->grouped();

        $this->assertSame(
            [
                SettingRegistry::GROUP_GENERAL,
                SettingRegistry::GROUP_WRITE,
                SettingRegistry::GROUP_CAPTURE,
                SettingRegistry::GROUP_REDACTION,
                SettingRegistry::GROUP_UI,
            ],
            array_keys($grouped),
        );
        $this->assertCount(4, $grouped[SettingRegistry::GROUP_GENERAL]);
    }

    public function test_an_unknown_key_is_not_found(): void
    {
        $this->assertNull((new SettingRegistry)->find('nope'));
    }
}
