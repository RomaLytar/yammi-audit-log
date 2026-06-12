<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract;

/**
 * Resolves request metadata (ip, url, method, user agent) for the change being
 * recorded. Returns an empty array outside HTTP or when the feature is off.
 */
interface RequestContextResolver
{
    /**
     * @return array<string, string>
     */
    public function resolve(): array;
}
