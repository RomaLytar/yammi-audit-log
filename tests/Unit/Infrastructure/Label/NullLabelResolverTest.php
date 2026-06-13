<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Label;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\Audit\ChangeData;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Infrastructure\Label\NullLabelResolver;

final class NullLabelResolverTest extends TestCase
{
    public function test_it_resolves_no_labels(): void
    {
        $change = new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: ChangeType::Updated,
            before: ['user_id' => 1],
            after: ['user_id' => 2],
        );

        $this->assertTrue((new NullLabelResolver)->labelsFor($change)->isEmpty());
    }
}
