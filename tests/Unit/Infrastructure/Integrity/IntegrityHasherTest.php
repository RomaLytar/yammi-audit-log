<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Integrity;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Integrity\IntegrityHasher;

final class IntegrityHasherTest extends TestCase
{
    public function test_the_hash_is_deterministic(): void
    {
        $hasher = new IntegrityHasher;
        $row = $this->row();

        $this->assertSame($hasher->hash(null, $row), $hasher->hash(null, $row));
        $this->assertSame(64, strlen($hasher->hash(null, $row)));
    }

    public function test_the_hash_covers_the_previous_link_and_the_payload(): void
    {
        $hasher = new IntegrityHasher;
        $row = $this->row();

        $genesis = $hasher->hash(null, $row);

        $this->assertNotSame($genesis, $hasher->hash($genesis, $row));

        $tampered = $row;
        $tampered['changes'] = ['status' => ['old' => 'a', 'new' => 'FORGED']];

        $this->assertNotSame($genesis, $hasher->hash(null, $tampered));
    }

    public function test_the_hash_covers_the_reason(): void
    {
        $hasher = new IntegrityHasher;
        $row = $this->row();

        $withReason = $row + ['reason' => 'ticket #4521'];
        $tampered = $row + ['reason' => 'forged'];

        $this->assertNotSame($hasher->hash(null, $row), $hasher->hash(null, $withReason));
        $this->assertNotSame($hasher->hash(null, $withReason), $hasher->hash(null, $tampered));
    }

    public function test_mutable_metadata_does_not_affect_the_hash(): void
    {
        $hasher = new IntegrityHasher;

        $row = $this->row();
        $withExtras = $row + ['integrity_hash' => 'x', 'is_noise' => true, 'context' => ['ip' => '1.2.3.4']];

        $this->assertSame($hasher->hash(null, $row), $hasher->hash(null, $withExtras));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(): array
    {
        return [
            'auditable_type' => 'App\\Models\\Order',
            'auditable_id' => '7',
            'event' => 'updated',
            'changes' => ['status' => ['old' => 'a', 'new' => 'b']],
            'actor_type' => 'user',
            'actor_id' => '1',
            'actor_label' => 'Jane',
            'origin_type' => null,
            'origin_id' => null,
            'origin_label' => null,
            'correlation_id' => 'corr-1',
            'occurred_at' => '2026-01-01 10:00:00',
        ];
    }
}
