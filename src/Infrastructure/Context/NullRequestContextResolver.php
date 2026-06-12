<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Context;

use Yammi\AuditLog\Application\Contract\RequestContextResolver;

/** @internal */
final class NullRequestContextResolver implements RequestContextResolver
{
    public function resolve(): array
    {
        return [];
    }
}
