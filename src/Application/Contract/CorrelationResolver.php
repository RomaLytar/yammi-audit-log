<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract;

/**
 * Resolves the correlation id of the current unit of work, so every change made
 * because of one root action (an HTTP request, a command or a job and the jobs
 * it dispatches) shares one id and can be drawn as a single causation chain.
 */
interface CorrelationResolver
{
    public function resolve(): ?string;
}
