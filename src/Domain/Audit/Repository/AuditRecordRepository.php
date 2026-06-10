<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\Repository;

use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

interface AuditRecordRepository
{
    public function save(AuditRecord $record): void;

    /**
     * @return list<AuditRecord>
     */
    public function timelineFor(AuditableReference $auditable, int $limit = 50): array;
}
