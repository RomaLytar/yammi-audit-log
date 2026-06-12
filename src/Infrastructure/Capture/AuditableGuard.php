<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Capture;

use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Contracts\ShouldAudit;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;

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
    ) {}

    public function shouldAudit(Model $model): bool
    {
        if ($model instanceof AuditRecordModel) {
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

        return true;
    }
}
