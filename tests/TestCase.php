<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Yammi\AuditLog\AuditLogServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [AuditLogServiceProvider::class];
    }
}
