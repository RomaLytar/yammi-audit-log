<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service\Definition;

use Yammi\AuditLog\Application\DTO\Settings\SettingDefinitionData;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

/** @internal */
final class RedactionSettings implements SettingGroupProvider
{
    public function definitions(): array
    {
        return [
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_REDACTION,
                key: 'redaction_keys',
                configPath: 'audit-log.redaction.keys',
                type: SettingType::CsvList,
                default: ['password', 'remember_token', 'token', 'secret', 'authorization', 'api_key', 'credit_card', 'ssn'],
                label: 'Secret key patterns',
                description: 'Comma-separated, case-insensitive substrings. Any field whose name contains one of these is stored redacted — including inside nested JSON values.',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_REDACTION,
                key: 'redaction_placeholder',
                configPath: 'audit-log.redaction.placeholder',
                type: SettingType::String,
                default: '[redacted]',
                label: 'Redaction placeholder',
                description: 'The text stored instead of a secret value.',
            ),
        ];
    }
}
