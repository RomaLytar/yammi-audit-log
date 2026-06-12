<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Tests\TestCase;

final class ApiDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_api_routes_are_off_by_default(): void
    {
        $this->getJson('audit-log/api/changes')->assertNotFound();
        $this->getJson('audit-log/api/stats')->assertNotFound();
    }
}
