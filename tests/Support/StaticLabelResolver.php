<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use Yammi\AuditLog\Application\Contract\LabelResolver;
use Yammi\AuditLog\Application\DTO\ChangeData;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;

final class StaticLabelResolver implements LabelResolver
{
    public function __construct(
        private readonly LabelSnapshot $labels,
    ) {}

    public function labelsFor(ChangeData $change): LabelSnapshot
    {
        return $this->labels;
    }
}
