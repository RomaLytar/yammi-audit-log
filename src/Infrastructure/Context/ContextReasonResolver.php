<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Context;

use Yammi\AuditLog\Application\Contract\ReasonResolver;

/** @internal */
final class ContextReasonResolver implements ReasonResolver
{
    public function __construct(
        private readonly ChangeReasonContext $context,
    ) {}

    public function resolve(): ?string
    {
        return $this->context->current();
    }
}
