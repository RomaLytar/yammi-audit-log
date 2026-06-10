<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\Query;

use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;

final class PagedRecords
{
    /**
     * @param  list<AuditRecord>  $records
     */
    public function __construct(
        public readonly array $records,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {}

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / max(1, $this->perPage)));
    }
}
