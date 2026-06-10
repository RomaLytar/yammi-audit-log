<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Label;

use Yammi\AuditLog\Application\Contract\LabelResolver;
use Yammi\AuditLog\Application\DTO\ChangeData;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;

final class NullLabelResolver implements LabelResolver
{
    public function labelsFor(ChangeData $change): LabelSnapshot
    {
        return LabelSnapshot::empty();
    }
}
