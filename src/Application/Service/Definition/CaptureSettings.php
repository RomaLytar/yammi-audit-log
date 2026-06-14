<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service\Definition;

use Yammi\AuditLog\Application\DTO\Settings\SettingDefinitionData;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

/** @internal */
final class CaptureSettings implements SettingGroupProvider
{
    public function definitions(): array
    {
        return [
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_CAPTURE,
                key: 'ignore_attributes',
                configPath: 'audit-log.capture.ignore_attributes',
                type: SettingType::CsvList,
                default: ['created_at', 'updated_at'],
                label: 'Ignored attributes',
                description: 'Comma-separated attributes dropped from every diff. An update touching only these is flagged as noise.',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_CAPTURE,
                key: 'request_context',
                configPath: 'audit-log.capture.request_context',
                type: SettingType::Boolean,
                default: false,
                label: 'Request metadata',
                description: 'Attach ip, url, method and user agent to changes captured during HTTP requests. This is PII — retention applies to it like to everything else.',
            ),
        ];
    }
}
