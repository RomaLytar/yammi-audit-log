<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Actor;

use Yammi\AuditLog\Application\Contract\ActorProvider;
use Yammi\AuditLog\Application\Contract\ActorResolver;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;

final class ActorResolverChain implements ActorResolver
{
    /**
     * @param  list<ActorProvider>  $providers
     */
    public function __construct(
        private readonly array $providers,
        private readonly ActorContext $context,
    ) {}

    public function resolve(): Actor
    {
        foreach ($this->providers as $provider) {
            $actor = $provider->resolve();

            if ($actor !== null) {
                return $actor;
            }
        }

        return Actor::system();
    }

    public function resolveOrigin(): ?Actor
    {
        $origin = $this->context->currentOrigin();

        if ($origin === null || $origin->isAnonymous()) {
            return null;
        }

        return $origin;
    }
}
