<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

final class InMemoryAuditRecordRepository implements AuditRecordRepository
{
    /** @var list<AuditRecord> */
    public array $saved = [];

    public function save(AuditRecord $record): void
    {
        $this->saved[] = $record;
    }

    public function timelineFor(AuditableReference $auditable, int $limit = 50): array
    {
        $matches = array_values(array_filter(
            $this->saved,
            static fn (AuditRecord $record): bool => $record->auditable()->equals($auditable),
        ));

        return array_slice(array_reverse($matches), 0, $limit);
    }
}
