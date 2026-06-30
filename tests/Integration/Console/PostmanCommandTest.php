<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Console;

use Yammi\AuditLog\Tests\TestCase;

final class PostmanCommandTest extends TestCase
{
    public function test_it_writes_a_postman_collection_to_a_file(): void
    {
        $path = sys_get_temp_dir().'/audit-log-postman-'.uniqid().'.json';

        $this->artisan('audit-log:postman', ['--output' => $path])->assertSuccessful();

        $collection = json_decode((string) file_get_contents($path), true);
        @unlink($path);

        $this->assertIsArray($collection);
        $this->assertStringContainsString('Audit Log API', $collection['info']['name']);
        $this->assertCount(9, $collection['item']);
    }

    public function test_it_prints_the_collection_to_stdout(): void
    {
        $this->artisan('audit-log:postman')->assertSuccessful();
    }
}
