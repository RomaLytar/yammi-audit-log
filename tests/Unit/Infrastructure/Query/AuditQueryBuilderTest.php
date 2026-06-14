<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Query;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\Audit\AuditFilterData;
use Yammi\AuditLog\Application\DTO\Audit\ChangeListData;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Infrastructure\Query\AuditQueryBuilder;

final class AuditQueryBuilderTest extends TestCase
{
    public function test_it_compiles_fluent_calls_to_the_changes_filter_array(): void
    {
        $captured = [];
        $result = new ChangeListData([], 0, 1, 25, 1, [], [], [], new AuditFilterData);

        $builder = new AuditQueryBuilder(function (array $filters) use (&$captured, $result): ChangeListData {
            $captured = $filters;

            return $result;
        });

        $returned = $builder
            ->model('App\\Models\\Order')
            ->event(ChangeType::Updated)
            ->actorType('job')
            ->actor('Sync')
            ->id('42')
            ->field('status')
            ->from('pending')
            ->to('paid')
            ->since('2026-01-01')
            ->until('2026-02-01')
            ->search('refund')
            ->onPage(3)
            ->get();

        $this->assertSame($result, $returned);
        $this->assertSame([
            'model' => 'App\\Models\\Order',
            'event' => 'updated',
            'actor_type' => 'job',
            'actor' => 'Sync',
            'id' => '42',
            'field' => 'status',
            'value_from' => 'pending',
            'value_to' => 'paid',
            'from' => '2026-01-01',
            'to' => '2026-02-01',
            'search' => 'refund',
            'page' => 3,
        ], $captured);
    }
}
