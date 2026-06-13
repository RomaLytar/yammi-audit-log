<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO\Audit;

final class RelatedChangeData
{
    public const VIA_CHAIN = 'chain';

    public const VIA_REFERENCE = 'reference';

    public function __construct(
        public readonly TimelineEntryData $entry,
        public readonly string $via,
    ) {}
}
