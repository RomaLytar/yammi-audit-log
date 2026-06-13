<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Policy;

use Illuminate\Database\Eloquent\Model;

/**
 * Holds the host-declared capture policies, one per model class. A policy
 * registered on a base class also applies to its subclasses. Empty by default,
 * so a fresh install captures everything.
 *
 * @internal
 */
final class AuditPolicyRegistry
{
    /**
     * @var array<class-string, AuditPolicy>
     */
    private array $policies = [];

    /**
     * @param  class-string  $model
     */
    public function policy(string $model): AuditPolicy
    {
        return $this->policies[$model] ??= new AuditPolicy($model);
    }

    public function for(Model $model): ?AuditPolicy
    {
        $class = $model::class;

        if (isset($this->policies[$class])) {
            return $this->policies[$class];
        }

        foreach ($this->policies as $registered => $policy) {
            if ($model instanceof $registered) {
                return $policy;
            }
        }

        return null;
    }
}
