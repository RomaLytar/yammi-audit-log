<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Actor\Provider;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Application\Contract\ActorProvider;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;

final class AuthenticatedUserProvider implements ActorProvider
{
    /**
     * @param  list<string>  $guards
     * @param  list<string>  $labelAttributes
     */
    public function __construct(
        private readonly AuthFactory $auth,
        private readonly array $guards = [],
        private readonly array $labelAttributes = ['name', 'title', 'email'],
    ) {}

    public function resolve(): ?Actor
    {
        $user = $this->authenticatedUser();

        if ($user === null) {
            return null;
        }

        $id = $user->getAuthIdentifier();

        if ($id === null) {
            return null;
        }

        return Actor::user(
            is_scalar($id) ? (string) $id : '',
            $this->labelFor($user),
        );
    }

    private function authenticatedUser(): ?Authenticatable
    {
        $guards = $this->guards === [] ? [null] : $this->guards;

        foreach ($guards as $guard) {
            $instance = $this->auth->guard($guard);

            if ($instance->check()) {
                return $instance->user();
            }
        }

        return null;
    }

    private function labelFor(Authenticatable $user): ?string
    {
        if (! $user instanceof Model) {
            return null;
        }

        foreach ($this->labelAttributes as $attribute) {
            $value = $user->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
