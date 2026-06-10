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

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('audit-log.ui.middleware', []);
    }
}
