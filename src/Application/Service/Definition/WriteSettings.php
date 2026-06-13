<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service\Definition;

use Yammi\AuditLog\Application\DTO\Settings\SettingDefinitionData;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

/** @internal */
final class WriteSettings implements SettingGroupProvider
{
    public function definitions(): array
    {
        return [
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_WRITE,
                key: 'write_async',
                configPath: 'audit-log.write.async',
                type: SettingType::Boolean,
                default: false,
                label: 'Async writes',
                description: 'Defer the audit insert to the queue. The actor, origin, correlation and redacted diff are still resolved at the moment of the change.',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_WRITE,
                key: 'write_queue',
                configPath: 'audit-log.write.queue',
                type: SettingType::String,
                default: '',
                label: 'Async queue name',
                description: 'Queue for the deferred insert. Empty = the default queue.',
            ),
            new SettingDefinitionData(
                group: SettingRegistry::GROUP_WRITE,
                key: 'integrity_enabled',
                configPath: 'audit-log.integrity.enabled',
                type: SettingType::Boolean,
                default: false,
                label: 'Hash-chain integrity',
                description: 'Chains every stored record to the previous one (sha256), so audit-log:verify can prove the history was not edited. Costs one extra select per insert.',
            ),
        ];
    }
}
