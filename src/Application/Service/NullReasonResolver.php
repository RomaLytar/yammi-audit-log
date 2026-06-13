<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service;

use Yammi\AuditLog\Application\Contract\ReasonResolver;

/** @internal */
final class NullReasonResolver implements ReasonResolver
{
    public function resolve(): ?string
    {
        return null;
    }
}
