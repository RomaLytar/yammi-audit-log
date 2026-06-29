<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Persistence\DTO;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Persistence\DTO\AuditRecordRow;

final class AuditRecordRowTest extends TestCase
{
    public function test_to_array_maps_every_column(): void
    {
        $row = new AuditRecordRow(
            auditableType: 'App\\Models\\Order',
            auditableId: '7',
            event: 'updated',
            changes: ['status' => ['old' => 'a', 'new' => 'b']],
            actorType: 'user',
            actorId: '1',
            actorLabel: 'Jane',
            originType: 'command',
            originId: 'app:sync',
            originLabel: 'app:sync',
            labels: ['user_id' => 'Jane'],
            correlationId: 'corr-1',
            isNoise: true,
            occurredAt: '2026-01-01 10:00:00',
            tenantId: 'acme',
            reason: 'ticket #4521',
            spanId: 'span-9',
            parentSpanId: 'span-8',
        );

        $this->assertSame([
            'auditable_type' => 'App\\Models\\Order',
            'auditable_id' => '7',
            'event' => 'updated',
            'changes' => ['status' => ['old' => 'a', 'new' => 'b']],
            'actor_type' => 'user',
            'actor_id' => '1',
            'actor_label' => 'Jane',
            'origin_type' => 'command',
            'origin_id' => 'app:sync',
            'origin_label' => 'app:sync',
            'labels' => ['user_id' => 'Jane'],
            'correlation_id' => 'corr-1',
            'is_noise' => true,
            'occurred_at' => '2026-01-01 10:00:00',
            'created_at' => '2026-01-01 10:00:00',
            'context' => [],
            'chain_depth' => 0,
            'tenant_id' => 'acme',
            'reason' => 'ticket #4521',
            'event_version' => 1,
            'span_id' => 'span-9',
            'parent_span_id' => 'span-8',
        ], $row->toArray());
    }
}
