<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit;

use Yammi\AuditLog\AuditLogServiceProvider;
use Yammi\AuditLog\Tests\TestCase;

final class AuditLogServiceProviderTest extends TestCase
{
    public function test_it_registers_the_service_provider(): void
    {
        $providers = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(AuditLogServiceProvider::class, $providers);
    }
}
