<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Contract;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\AnomalyData;
use Yammi\AuditLog\Application\DTO\AnomalyWindow;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Tests\Support\Anomaly\PriceDropRule;

final class AnomalyRuleTest extends TestCase
{
    public function test_a_rule_can_be_evaluated_in_isolation_over_entries(): void
    {
        $rule = new PriceDropRule;

        $findings = $rule->evaluate(
            [
                $this->entry(['price' => ['old' => 100, 'new' => 50]]),
                $this->entry(['price' => ['old' => 100, 'new' => 150]]),
                $this->entry(['status' => ['old' => 'a', 'new' => 'b']]),
            ],
            new AnomalyWindow(new DateTimeImmutable('2026-01-01T00:00:00+00:00'), new DateTimeImmutable('2026-01-01T01:00:00+00:00')),
        );

        $this->assertCount(1, $findings);
        $this->assertSame('price_drop', $findings[0]->rule);
        $this->assertSame(AnomalyData::SEVERITY_HIGH, $findings[0]->severity);
    }

    /**
     * @param  array<string, array{old: scalar|array<array-key, mixed>|null, new: scalar|array<array-key, mixed>|null}>  $changes
     */
    private function entry(array $changes): TimelineEntryData
    {
        return new TimelineEntryData(
            id: 1,
            auditableType: 'App\\Models\\Product',
            auditableId: '7',
            event: 'updated',
            actorType: 'user',
            actorLabel: 'Jane',
            originLabel: null,
            changes: $changes,
            labels: [],
            occurredAt: '2026-01-01T00:30:00+00:00',
            correlationId: null,
        );
    }
}
