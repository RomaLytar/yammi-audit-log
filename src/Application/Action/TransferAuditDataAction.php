<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action;

use Yammi\AuditLog\Application\Contract\AuditDataTransferrer;
use Yammi\AuditLog\Application\DTO\TransferResultData;

/** @internal */
final class TransferAuditDataAction
{
    public function __construct(
        private readonly AuditDataTransferrer $transferrer,
    ) {}

    public function __invoke(string $from, string $to, bool $deleteSource): TransferResultData
    {
        return $this->transferrer->transfer($from, $to, $deleteSource);
    }
}
