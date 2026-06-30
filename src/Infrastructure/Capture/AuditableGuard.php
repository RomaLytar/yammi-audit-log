<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Capture;

use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Application\Contract\Resolver\CorrelationResolver;
use Yammi\AuditLog\Contracts\ShouldAudit;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditCaptureFailureModel;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditChainStateModel;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Infrastructure\Policy\AuditPolicy;
use Yammi\AuditLog\Infrastructure\Policy\AuditPolicyRegistry;

/** @internal */
final class AuditableGuard
{
    public const MODE_ALL = 'all';

    public const MODE_OPT_IN = 'opt_in';

    /**
     * @param  list<string>  $excluded
     */
    public function __construct(
        private readonly array $excluded,
        private readonly string $mode = self::MODE_ALL,
        private readonly AuditPolicyRegistry $policies = new AuditPolicyRegistry,
        private readonly ?CorrelationResolver $correlations = null,
    ) {}

    public function shouldAudit(Model $model): bool
    {
        if ($model instanceof AuditRecordModel || $model instanceof AuditChainStateModel || $model instanceof AuditCaptureFailureModel) {
            return false;
        }

        if ($model->getKey() === null) {
            return false;
        }

        if ($this->mode === self::MODE_OPT_IN && ! $model instanceof ShouldAudit) {
            return false;
        }

        foreach ($this->excluded as $class) {
            if (is_a($model, $class)) {
                return false;
            }
        }

        $policy = $this->policies->for($model);

        if ($policy === null) {
            return true;
        }

        return $policy->allows($model) && $this->passesSampling($policy, $model);
    }

    /**
     * Deterministic per (correlation, model): the whole record's history inside
     * one unit of work is kept or dropped together, no per-event holes. Outside
     * a correlation there is nothing stable to key on, so we keep the change.
     */
    private function passesSampling(AuditPolicy $policy, Model $model): bool
    {
        $rate = $policy->sampleRate();

        if ($rate === null || $rate >= 1.0) {
            return true;
        }

        if ($rate <= 0.0) {
            return false;
        }

        $correlation = $this->correlations?->resolve();

        if ($correlation === null) {
            return true;
        }

        return crc32($correlation.'|'.$model::class) % 1000 / 1000.0 < $rate;
    }
}
