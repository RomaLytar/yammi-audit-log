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

    public function test_the_prune_schedule_toggle_is_registered(): void
    {
        $definition = (new SettingRegistry)->find('prune_schedule_enabled');

        $this->assertNotNull($definition);
        $this->assertSame('audit-log.retention.schedule.enabled', $definition->configPath);
        $this->assertSame(SettingType::Boolean, $definition->type);
        $this->assertTrue($definition->default);
    }

    public function test_an_unknown_key_is_not_found(): void
    {
        $this->assertNull((new SettingRegistry)->find('nope'));
    }

    public function test_every_definition_belongs_to_the_general_group(): void
    {
        foreach ((new SettingRegistry)->all() as $definition) {
            $this->assertSame(SettingRegistry::GROUP_GENERAL, $definition->group);
        }
    }
}
