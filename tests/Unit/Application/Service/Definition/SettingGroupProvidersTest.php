<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Service\Definition;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Service\Definition\AlertSettings;
use Yammi\AuditLog\Application\Service\Definition\AnomalySettings;
use Yammi\AuditLog\Application\Service\Definition\CaptureSettings;
use Yammi\AuditLog\Application\Service\Definition\GeneralSettings;
use Yammi\AuditLog\Application\Service\Definition\RedactionSettings;
use Yammi\AuditLog\Application\Service\Definition\SettingGroupProvider;
use Yammi\AuditLog\Application\Service\Definition\UiSettings;
use Yammi\AuditLog\Application\Service\Definition\WriteSettings;
use Yammi\AuditLog\Application\Service\SettingRegistry;

final class SettingGroupProvidersTest extends TestCase
{
    /**
     * @return iterable<string, array{SettingGroupProvider, string, int}>
     */
    public static function providers(): iterable
    {
        yield 'general' => [new GeneralSettings, SettingRegistry::GROUP_GENERAL, 4];
        yield 'write' => [new WriteSettings, SettingRegistry::GROUP_WRITE, 3];
        yield 'capture' => [new CaptureSettings, SettingRegistry::GROUP_CAPTURE, 2];
        yield 'redaction' => [new RedactionSettings, SettingRegistry::GROUP_REDACTION, 2];
        yield 'alerts' => [new AlertSettings, SettingRegistry::GROUP_ALERTS, 3];
        yield 'anomalies' => [new AnomalySettings, SettingRegistry::GROUP_ANOMALIES, 4];
        yield 'ui' => [new UiSettings, SettingRegistry::GROUP_UI, 4];
    }

    /**
     * @dataProvider providers
     */
    public function test_each_provider_yields_only_its_own_group(SettingGroupProvider $provider, string $group, int $count): void
    {
        $definitions = $provider->definitions();

        $this->assertCount($count, $definitions);

        foreach ($definitions as $definition) {
            $this->assertSame($group, $definition->group);
        }
    }
}
