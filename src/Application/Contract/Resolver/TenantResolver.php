<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract\Resolver;

/**
 * Resolves the tenant the current unit of work belongs to. When it returns
 * an id, every new audit record is stamped with it and every read —
 * dashboard, facades, API, exports — is scoped to it automatically.
 * Point audit-log.tenancy.resolver at your implementation; returning null
 * means single-tenant (no scoping).
 */
interface TenantResolver
{
    public function resolve(): ?string;
}
