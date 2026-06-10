<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Capture;

use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;

final class AuditableGuard
{
    /**
     * @param  list<string>  $excluded
     */
    public function __construct(
        private readonly array $excluded,
    ) {}

    public function shouldAudit(Model $model): bool
    {
        if ($model instanceof AuditRecordModel) {
            return false;
        }

        if ($model->getKey() === null) {
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
