<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Service;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Service\PostmanCollectionBuilder;

final class PostmanCollectionBuilderTest extends TestCase
{
    public function test_it_builds_a_v21_collection_with_bearer_auth_and_variables(): void
    {
        $collection = (new PostmanCollectionBuilder)->build('Acme Audit Log API', 'audit-log/api');

        $this->assertSame('Acme Audit Log API', $collection['info']['name']);
        $this->assertStringContainsString('v2.1.0', $collection['info']['schema']);
        $this->assertSame('bearer', $collection['auth']['type']);

        $variableKeys = array_column($collection['variable'], 'key');
        $this->assertContains('base_url', $variableKeys);
        $this->assertContains('token', $variableKeys);
        $this->assertContains('correlation', $variableKeys);

        $this->assertCount(9, $collection['item']);
    }

    public function test_each_request_targets_the_configured_api_path(): void
    {
        $collection = (new PostmanCollectionBuilder)->build('X', 'custom/audit/api');

        $changes = $this->itemNamed($collection, 'List changes');

        $this->assertSame(['custom', 'audit', 'api', 'changes'], $changes['request']['url']['path']);
        $this->assertStringStartsWith('{{base_url}}/custom/audit/api/changes', $changes['request']['url']['raw']);

        $queryKeys = array_column($changes['request']['url']['query'], 'key');
        $this->assertContains('model', $queryKeys);
        $this->assertContains('page', $queryKeys);
    }

    public function test_the_chain_request_uses_the_correlation_variable(): void
    {
        $collection = (new PostmanCollectionBuilder)->build('X', 'audit-log/api');

        $chain = $this->itemNamed($collection, 'Change chain');

        $this->assertContains('{{correlation}}', $chain['request']['url']['path']);
    }

    /**
     * @param  array<string, mixed>  $collection
     * @return array<string, mixed>
     */
    private function itemNamed(array $collection, string $name): array
    {
        foreach ($collection['item'] as $item) {
            if ($item['name'] === $name) {
                return $item;
            }
        }

        $this->fail("No Postman item named {$name}.");
    }
}
