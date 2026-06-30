<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Capture;

use Illuminate\Database\Eloquent\Model;
use Throwable;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;

/**
 * Handles a failure of the fail-open capture path, so an audit gap is visible
 * instead of silently swallowed.
 *
 * @internal
 */
interface CaptureFailureReporter
{
    public function record(?Model $model, ?ChangeType $event, Throwable $exception): void;
}
