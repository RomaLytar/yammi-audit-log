<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Reader;

use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Application\Action\BuildTimelineAction;
use Yammi\AuditLog\Application\DTO\TimelineData;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

final class AuditReader
{
    public function __construct(
        private readonly BuildTimelineAction $buildTimeline,
    ) {}

    public function for(Model|string $auditable, int|string|null $id = null, int $limit = 50): TimelineData
    {
        $reference = $auditable instanceof Model
            ? AuditableReference::to($auditable->getMorphClass(), (string) $auditable->getKey())
            : AuditableReference::to($auditable, (string) $id);

        return ($this->buildTimeline)($reference, $limit);
    }
}
