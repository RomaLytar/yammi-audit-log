<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract;

use Yammi\AuditLog\Application\DTO\ChangeData;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;

interface LabelResolver
{
    public function labelsFor(ChangeData $change): LabelSnapshot;
}
