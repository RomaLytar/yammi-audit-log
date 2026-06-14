<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Action;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\Transfer\TransferAuditDataAction;
use Yammi\AuditLog\Tests\Support\FakeAuditDataTransferrer;

final class TransferAuditDataActionTest extends TestCase
{
    public function test_it_delegates_to_the_transferrer_and_returns_the_result(): void
    {
        $transferrer = new FakeAuditDataTransferrer(rowsMoved: 42);
        $action = new TransferAuditDataAction($transferrer);

        $result = $action('source', 'target', true);

        $this->assertSame(42, $result->rowsMoved);
        $this->assertSame('source', $transferrer->from);
        $this->assertSame('target', $transferrer->to);
        $this->assertTrue($transferrer->deleteSource);
    }
}
