<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract;

use Yammi\AuditLog\Application\DTO\TransferResultData;

/**
 * Moves audit rows between two configured database connections, so a host can
 * adopt (or leave) a dedicated audit database without losing history.
 */
interface AuditDataTransferrer
{
    public function transfer(string $from, string $to, bool $deleteSource): TransferResultData;
}
