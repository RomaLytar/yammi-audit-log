<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\DTO;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\SettingDefinitionData;
use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

final class SettingDefinitionDataTest extends TestCase
{
    public function test_integers_are_clamped_to_the_declared_bounds(): void
    {
        $definition = $this->definition(min: 7, max: 9999);

        $this->assertSame(7, $definition->clamp(1));
        $this->assertSame(7, $definition->clamp(7));
        $this->assertSame(500, $definition->clamp(500));
        $this->assertSame(9999, $definition->clamp(10000));
    }

    public function test_values_without_bounds_pass_through(): void
    {
        $this->assertSame(123456, $this->definition()->clamp(123456));
    }

    public function test_non_integers_are_never_clamped(): void
    {
        $this->assertTrue($this->definition(min: 7)->clamp(true));
        $this->assertSame('x', $this->definition(min: 7)->clamp('x'));
    }

    private function definition(?int $min = null, ?int $max = null): SettingDefinitionData
    {
        return new SettingDefinitionData(
            group: 'general',
            key: 'k',
            configPath: 'audit-log.k',
            type: SettingType::Integer,
            default: 1,
            label: 'K',
            description: 'd',
            min: $min,
            max: $max,
        );
    }
}
