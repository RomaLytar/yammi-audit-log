<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract\Resolver;

/**
 * Resolves the reason ("why") attached to the change currently being recorded,
 * set by the host around a unit of work (e.g. AuditLog::withReason()).
 */
interface ReasonResolver
{
    public function resolve(): ?string;
}
