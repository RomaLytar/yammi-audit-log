<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO;

/** @internal */
final class TransferResultData
{
    public function __construct(
        public readonly int $rowsMoved,
    ) {}
}
