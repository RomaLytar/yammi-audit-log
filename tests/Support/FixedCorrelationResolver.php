<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use Yammi\AuditLog\Application\Contract\Resolver\CorrelationResolver;

final class FixedCorrelationResolver implements CorrelationResolver
{
    public function __construct(
        private readonly ?string $id = null,
    ) {}

    public function resolve(): ?string
    {
        return $this->id;
    }
}
