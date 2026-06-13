<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use Yammi\AuditLog\Application\Contract\AuditDataTransferrer;
use Yammi\AuditLog\Application\DTO\Transfer\TransferResultData;

final class FakeAuditDataTransferrer implements AuditDataTransferrer
{
    public ?string $from = null;

    public ?string $to = null;

    public ?bool $deleteSource = null;

    public function __construct(
        private readonly int $rowsMoved = 0,
    ) {}

    public function transfer(string $from, string $to, bool $deleteSource): TransferResultData
    {
        $this->from = $from;
        $this->to = $to;
        $this->deleteSource = $deleteSource;

        return new TransferResultData($this->rowsMoved);
    }
}
