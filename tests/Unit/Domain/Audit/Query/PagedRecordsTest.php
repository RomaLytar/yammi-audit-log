<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Domain\Audit\Query;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\Query\PagedRecords;

final class PagedRecordsTest extends TestCase
{
    public function test_last_page_is_one_when_empty(): void
    {
        $paged = new PagedRecords([], 0, 1, 25);

        $this->assertSame(1, $paged->lastPage());
    }

    public function test_last_page_rounds_up(): void
    {
        $this->assertSame(1, (new PagedRecords([], 25, 1, 25))->lastPage());
        $this->assertSame(2, (new PagedRecords([], 26, 1, 25))->lastPage());
        $this->assertSame(3, (new PagedRecords([], 51, 1, 25))->lastPage());
    }

    public function test_last_page_guards_against_a_zero_page_size(): void
    {
        $this->assertSame(10, (new PagedRecords([], 10, 1, 0))->lastPage());
    }
}
