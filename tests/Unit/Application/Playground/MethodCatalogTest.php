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

        $this->assertSame(['for', 'stateAt', 'changes', 'noise', 'chain', 'stats', 'recordView', 'subjectReport', 'record'], $keys);
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

    public function test_only_the_write_method_is_destructive(): void
    {
        $catalog = new MethodCatalog;

        $this->assertFalse($catalog->find('for')?->destructive);
        $this->assertTrue($catalog->find('record')?->destructive);
    }

    public function test_an_unknown_method_is_not_found(): void
    {
        $this->assertNull((new MethodCatalog)->find('nope'));
    }
}
