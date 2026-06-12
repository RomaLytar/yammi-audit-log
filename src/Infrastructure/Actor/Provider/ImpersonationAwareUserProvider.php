<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Actor\Provider;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Model;
use Throwable;
use Yammi\AuditLog\Application\Contract\ActorProvider;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Infrastructure\Context\RequestContextHolder;

/**
 * Makes user attribution impersonation-proof: when an admin works as
 * another user (login-as), the session marker left by impersonation
 * packages is picked up and the actor label names both people —
 * "Jane Doe (impersonated by Support Admin)". The identifier stays the
 * impersonated account so per-user queries keep working.
 *
 * @internal
 */
final class ImpersonationAwareUserProvider implements ActorProvider
{
    private const LABEL_ATTRIBUTES = ['name', 'title', 'email'];

    /**
     * @param  list<string>  $sessionKeys
     * @param  list<string>  $guards
     */
    public function __construct(
        private readonly AuthenticatedUserProvider $users,
        private readonly RequestContextHolder $requests,
        private readonly AuthFactory $auth,
        private readonly array $sessionKeys = ['impersonated_by'],
        private readonly array $guards = [],
    ) {}

    public function resolve(): ?Actor
    {
        $actor = $this->users->resolve();

        if ($actor === null) {
            return null;
        }

        $impersonatorId = $this->impersonatorId();

        if ($impersonatorId === null || $impersonatorId === $actor->identifier) {
            return $actor;
        }

        return Actor::user(
            (string) $actor->identifier,
            $actor->displayLabel().' (impersonated by '.$this->impersonatorLabel($impersonatorId).')',
        );
    }

    private function impersonatorId(): ?string
    {
        $request = $this->requests->current();

        if ($request === null || ! $request->hasSession()) {
            return null;
        }

        $session = $request->session();

        foreach ($this->sessionKeys as $key) {
            $value = $session->get($key);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function impersonatorLabel(string $id): string
    {
        try {
            foreach ($this->guards === [] ? [null] : $this->guards as $guardName) {
                $guard = $this->auth->guard($guardName);

                if (! $guard instanceof SessionGuard) {
                    continue;
                }

                $impersonator = $guard->getProvider()->retrieveById($id);

                if ($impersonator instanceof Model) {
                    foreach (self::LABEL_ATTRIBUTES as $attribute) {
                        $value = $impersonator->getAttribute($attribute);

                        if (is_string($value) && $value !== '') {
                            return $value;
                        }
                    }
                }

                if ($impersonator !== null) {
                    break;
                }
            }
        } catch (Throwable) {
            return '#'.$id;
        }

        return '#'.$id;
    }
}
