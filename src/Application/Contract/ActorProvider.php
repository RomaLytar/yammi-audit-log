<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract;

use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;

/**
 * One level of actor attribution. Providers are tried in order; the first to
 * return a non-null actor wins. Host apps add their own providers to extend
 * attribution to new kinds of actor.
 */
interface ActorProvider
{
    public function resolve(): ?Actor;
}
