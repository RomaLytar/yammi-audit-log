<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Actor\Provider;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Throwable;
use Yammi\AuditLog\Application\Contract\Actor\ActorProvider;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;

/**
 * Adds API-token provenance to a user actor: when the change was made through a
 * Sanctum personal-access token or a Passport OAuth client, the actor label
 * names it too, "Jane Doe via mobile-app". The identifier stays the user so
 * per-user queries keep working. Sanctum/Passport are duck-typed and optional;
 * resolution is fail-soft, so neither package is required.
 *
 * @internal
 */
final class TokenAwareUserProvider implements ActorProvider
{
    /**
     * @param  list<string>  $guards
     */
    public function __construct(
        private readonly ActorProvider $inner,
        private readonly AuthFactory $auth,
        private readonly array $guards = [],
    ) {}

    public function resolve(): ?Actor
    {
        $actor = $this->inner->resolve();

        if ($actor === null || $actor->type !== ActorType::User) {
            return $actor;
        }

        $token = $this->tokenName();

        if ($token === null) {
            return $actor;
        }

        return Actor::user((string) $actor->identifier, $actor->displayLabel().' via '.$token);
    }

    private function tokenName(): ?string
    {
        try {
            foreach ($this->guards === [] ? [null] : $this->guards as $guardName) {
                $user = $this->auth->guard($guardName)->user();

                if ($user === null) {
                    continue;
                }

                $name = $this->extract($user);

                if ($name !== null) {
                    return $name;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function extract(object $user): ?string
    {
        if (method_exists($user, 'currentAccessToken')) {
            $name = $this->propertyString($user->currentAccessToken(), 'name');

            if ($name !== null) {
                return $name;
            }
        }

        if (method_exists($user, 'token')) {
            $client = $this->property($user->token(), 'client');

            $name = $this->propertyString($client, 'name');

            if ($name !== null) {
                return $name;
            }
        }

        return null;
    }

    private function propertyString(mixed $object, string $name): ?string
    {
        $value = $this->property($object, $name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function property(mixed $object, string $name): mixed
    {
        return is_object($object) && property_exists($object, $name) ? $object->{$name} : null;
    }
}
