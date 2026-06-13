<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\DTO;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\Audit\ChangeData;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;

final class ChangeDataTest extends TestCase
{
    public function test_reference_points_at_the_auditable(): void
    {
        $change = new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '7',
            event: ChangeType::Updated,
            before: ['status' => 'a'],
            after: ['status' => 'b'],
        );

        $reference = $change->reference();

        $this->assertSame('App\\Models\\Order', $reference->type);
        $this->assertSame('7', $reference->id);
    }
}
