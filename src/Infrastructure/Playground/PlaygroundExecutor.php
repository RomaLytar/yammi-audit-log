<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Playground;

use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;
use Yammi\AuditLog\Infrastructure\AuditLogManager;

/**
 * Runs one whitelisted facade method with arguments from the playground form.
 *
 * @internal
 */
final class PlaygroundExecutor
{
    public function __construct(
        private readonly AuditLogManager $manager,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     */
    public function execute(string $key, array $args): mixed
    {
        return match ($key) {
            'for' => $this->manager->for(
                $this->stringArg($args, 'auditable_type'),
                $this->stringArg($args, 'auditable_id'),
                $this->intArg($args, 'limit', 50),
            ),
            'record' => $this->manager->record(
                $this->stringArg($args, 'auditable_type'),
                $this->stringArg($args, 'auditable_id'),
                $this->stringArg($args, 'event'),
                $this->jsonArg($args, 'before'),
                $this->jsonArg($args, 'after'),
            ),
            default => throw InvalidAuditData::emptyValue('playground method'),
        };
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function stringArg(array $args, string $name): string
    {
        $value = $args[$name] ?? null;

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function intArg(array $args, string $name, int $default): int
    {
        $value = $args[$name] ?? null;

        return is_numeric($value) ? max(1, (int) $value) : $default;
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, scalar|array<array-key, mixed>|null>
     */
    private function jsonArg(array $args, string $name): array
    {
        $raw = $args[$name] ?? null;

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            throw InvalidAuditData::emptyValue("valid JSON object for \"{$name}\"");
        }

        $out = [];

        foreach ($decoded as $field => $value) {
            if (is_scalar($value) || is_array($value) || $value === null) {
                $out[(string) $field] = $value;
            }
        }

        return $out;
    }
}
