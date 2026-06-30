<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Concerns;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Application\DTO\Audit\StateData;
use Yammi\AuditLog\Application\DTO\Audit\TimelineData;
use Yammi\AuditLog\Infrastructure\AuditLogManager;

/**
 * Opt-in convenience accessors for a model's audit trail, so a host that prefers
 * Eloquent-style access can write $order->auditTrail() instead of
 * AuditLog::for($order). Capture stays global and automatic; this only adds
 * read sugar and returns the same DTOs as the facade (no Eloquent rows leak out).
 *
 * @mixin Model
 */
trait HasAuditTrail
{
    public function auditTrail(int $limit = 50): TimelineData
    {
        return app(AuditLogManager::class)->for($this->getMorphClass(), (string) $this->getKey(), $limit);
    }

    public function auditStateAt(DateTimeImmutable|string|null $at = null): StateData
    {
        return app(AuditLogManager::class)->stateAt($this->getMorphClass(), (string) $this->getKey(), $at);
    }
}
