<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use DateTimeImmutable;
use RuntimeException;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

final class ThrowingAuditRecordRepository implements AuditRecordRepository
{
    public function save(AuditRecord $record): void
    {
        throw new RuntimeException('storage unavailable');
    }

    public function timelineFor(AuditableReference $auditable, int $limit = 50): array
    {
        throw new RuntimeException('storage unavailable');
    }

    public function deleteOlderThan(DateTimeImmutable $cutoff): int
    {
        throw new RuntimeException('storage unavailable');
    }
}
