<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Concerns;

use Illuminate\Database\Eloquent\Model;
use Psr\Log\LoggerInterface;
use Throwable;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Infrastructure\AuditLogManager;

/**
 * Opt-in read auditing for host models. Call recordAccess() wherever a record
 * is viewed to capture "who looked at this" — a read is an event too under
 * HIPAA/GDPR. The access is attributed through the same pipeline as captured
 * changes and carries no diff. Fails closed: a logging failure never breaks
 * the read path.
 *
 * @mixin Model
 */
trait LogsAccess
{
    public function recordAccess(): ?TimelineEntryData
    {
        try {
            return app(AuditLogManager::class)->recordAccess($this->getMorphClass(), (string) $this->getKey());
        } catch (Throwable $exception) {
            app(LoggerInterface::class)->error(
                'Audit access capture failed: '.$exception->getMessage(),
                ['exception' => $exception],
            );

            return null;
        }
    }
}
