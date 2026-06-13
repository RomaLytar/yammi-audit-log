<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Policy;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * One model's capture policy: a single, fluent place to declare what is audited
 * for a model, on top of the safe "capture everything" default. Hosts build
 * these through AuditLog::policy(Model::class). Sampling is layered on later.
 *
 * @internal
 */
final class AuditPolicy
{
    /**
     * @var list<string>
     */
    private array $ignored = [];

    /**
     * @var (Closure(Model): bool)|null
     */
    private ?Closure $condition = null;

    private ?float $sampleRate = null;

    public function __construct(
        public readonly string $model,
    ) {}

    /**
     * Drop these attributes from every diff for this model, on top of the
     * model's own $auditExclude and the global ignore_attributes.
     *
     * @param  list<string>  $fields
     */
    public function ignore(array $fields): self
    {
        $this->ignored = array_values(array_filter($fields, is_string(...)));

        return $this;
    }

    /**
     * Capture a change only when the predicate returns true (e.g. by tenant,
     * environment or model state). No predicate means always capture.
     *
     * @param  Closure(Model): bool  $condition
     */
    public function when(Closure $condition): self
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Keep only a fraction (0.0–1.0) of this model's changes. The decision is
     * made per correlation, so a whole record's history inside one unit of work
     * is kept or dropped together, never left with holes. For thinning noisy,
     * high-churn models without losing important ones.
     */
    public function sample(float $rate): self
    {
        $this->sampleRate = max(0.0, min(1.0, $rate));

        return $this;
    }

    /**
     * @return list<string>
     */
    public function ignoredFields(): array
    {
        return $this->ignored;
    }

    public function sampleRate(): ?float
    {
        return $this->sampleRate;
    }

    public function allows(Model $model): bool
    {
        return $this->condition === null || ($this->condition)($model) === true;
    }
}
