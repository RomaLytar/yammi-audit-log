<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Playground;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Playground\MethodCatalog;

final class MethodCatalogTest extends TestCase
{
    public function test_the_whole_public_facade_is_catalogued(): void
    {
        $keys = array_map(
            static fn ($method): string => $method->key,
            (new MethodCatalog)->all(),
        );

        $this->assertSame(['for', 'stateAt', 'changes', 'noise', 'chain', 'stats', 'anomalies', 'recordView', 'subjectReport', 'record', 'recordAccess', 'activityUrl'], $keys);
    }

    public function test_each_method_carries_docs_and_a_real_example(): void
    {
        foreach ((new MethodCatalog)->all() as $method) {
            $this->assertNotSame('', $method->signature);
            $this->assertNotSame('', $method->summary);
            $this->assertStringContainsString('AuditLog::'.$method->key, $method->example);
            $this->assertNotSame([], $method->arguments);
        }
    }

    public function test_write_methods_are_flagged_destructive(): void
    {
        $catalog = new MethodCatalog;

        $this->assertFalse($catalog->find('for')?->destructive);
        $this->assertFalse($catalog->find('activityUrl')?->destructive);
        $this->assertTrue($catalog->find('record')?->destructive);
        $this->assertTrue($catalog->find('recordAccess')?->destructive);
    }

    public function test_an_unknown_method_is_not_found(): void
    {
        $this->assertNull((new MethodCatalog)->find('nope'));
    }
}
