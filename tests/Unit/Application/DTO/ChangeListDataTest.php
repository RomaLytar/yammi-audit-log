<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\DTO;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\Audit\AuditFilterData;
use Yammi\AuditLog\Application\DTO\Audit\ChangeListData;

final class ChangeListDataTest extends TestCase
{
    public function test_chain_size_defaults_to_one(): void
    {
        $list = $this->list(correlationSizes: ['abc' => 4]);

        $this->assertSame(4, $list->chainSize('abc'));
        $this->assertSame(1, $list->chainSize('unknown'));
        $this->assertSame(1, $list->chainSize(null));
    }

    public function test_is_empty_reflects_the_entries(): void
    {
        $this->assertTrue($this->list()->isEmpty());
    }

    /**
     * @param  array<string, int>  $correlationSizes
     */
    private function list(array $correlationSizes = []): ChangeListData
    {
        return new ChangeListData(
            entries: [],
            total: 0,
            page: 1,
            perPage: 25,
            lastPage: 1,
            models: [],
            actorTypes: [],
            events: [],
            filters: new AuditFilterData,
            correlationSizes: $correlationSizes,
        );
    }
}
