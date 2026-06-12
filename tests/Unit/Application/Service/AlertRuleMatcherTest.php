<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Service;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Application\Service\AlertRuleMatcher;

final class AlertRuleMatcherTest extends TestCase
{
    public function test_it_matches_on_model_attribute_and_event(): void
    {
        $matcher = new AlertRuleMatcher;
        $entry = $this->entry('App\\Models\\User', 'updated', ['role' => ['old' => 'user', 'new' => 'admin']]);

        $rule = ['model' => 'App\\Models\\User', 'attributes' => ['role'], 'events' => ['updated']];

        $this->assertSame([$rule], $matcher->matching([$rule], $entry));
    }

    public function test_empty_attribute_and_event_lists_mean_any(): void
    {
        $matcher = new AlertRuleMatcher;
        $entry = $this->entry('App\\Models\\User', 'deleted', []);

        $this->assertCount(1, $matcher->matching([['model' => 'App\\Models\\User']], $entry));
    }

    public function test_mismatches_are_rejected(): void
    {
        $matcher = new AlertRuleMatcher;
        $entry = $this->entry('App\\Models\\User', 'updated', ['name' => ['old' => 'a', 'new' => 'b']]);

        $this->assertSame([], $matcher->matching([
            ['model' => 'App\\Models\\Order'],
            ['model' => 'App\\Models\\User', 'attributes' => ['role']],
            ['model' => 'App\\Models\\User', 'events' => ['deleted']],
            ['model' => ''],
            'not-a-rule',
        ], $entry));
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    private function entry(string $type, string $event, array $changes): TimelineEntryData
    {
        return new TimelineEntryData(
            id: 1,
            auditableType: $type,
            auditableId: '1',
            event: $event,
            actorType: 'user',
            actorLabel: 'Jane',
            originLabel: null,
            changes: $changes,
            labels: [],
            occurredAt: '2026-01-01T10:00:00+00:00',
            correlationId: null,
        );
    }
}
