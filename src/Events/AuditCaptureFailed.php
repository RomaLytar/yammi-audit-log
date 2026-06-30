<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Events;

use Throwable;

/**
 * Fired when the automatic (Eloquent) capture pipeline failed to record a model
 * change. The write path is fail-open, so the host operation still succeeded;
 * listen to this to alert on audit gaps. The package also records each failure
 * to its own table for the dashboard's capture-health view.
 */
final class AuditCaptureFailed
{
    public function __construct(
        public readonly ?string $auditableType,
        public readonly ?string $event,
        public readonly Throwable $exception,
    ) {}
}
