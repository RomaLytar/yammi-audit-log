<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service\Definition;

use Yammi\AuditLog\Application\DTO\Settings\SettingDefinitionData;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

/** @internal */
final class ObservabilitySettings implements SettingGroupProvider
{
    public function definitions(): array
    {
        return [
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_OBSERVABILITY,
                key: 'observability_trace_url',
                configPath: 'audit-log.integrations.observability.trace_url',
                type: SettingType::String,
                default: '',
                label: 'APM trace URL',
                description: 'Template URL of your tracing backend, with {trace_id} as the placeholder (e.g. https://app.datadoghq.com/apm/trace/{trace_id}). When set, a chain that carried a W3C traceparent shows an "Open distributed trace" link. Empty = show the raw id only.',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_OBSERVABILITY,
                key: 'observability_postman',
                configPath: 'audit-log.api.postman',
                type: SettingType::Boolean,
                default: true,
                label: 'Postman export',
                description: 'Offer the read API as a Postman collection: the "Download Postman collection" button and the audit-log:postman command. Needs the API enabled.',
            ),
        ];
    }
}
