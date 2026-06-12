<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Contracts;

/**
 * Marker for models that must be captured when audit-log.capture.mode is
 * "opt_in". In the default "all" mode every model is captured and this
 * interface is not needed.
 */
interface ShouldAudit {}
