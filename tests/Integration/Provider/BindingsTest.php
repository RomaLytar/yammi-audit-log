<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Provider;

use Yammi\AuditLog\Application\Contract\Query\AuditLogQuery;
use Yammi\AuditLog\Application\Contract\Resolver\ActorResolver;
use Yammi\AuditLog\Application\Contract\Resolver\TenantResolver;
use Yammi\AuditLog\Application\Contract\ValueRedactor;
use Yammi\AuditLog\Application\Pipeline\RecordChangePipeline;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Infrastructure\Actor\ActorResolverChain;
use Yammi\AuditLog\Infrastructure\Alert\AlertChannels;
use Yammi\AuditLog\Infrastructure\Anomaly\AnomalyScanner;
use Yammi\AuditLog\Infrastructure\Integrity\IntegritySigner;
use Yammi\AuditLog\Infrastructure\Persistence\Query\EloquentAuditLogQuery;
use Yammi\AuditLog\Infrastructure\Persistence\Repository\AuditRowWriter;
use Yammi\AuditLog\Infrastructure\Persistence\Repository\EloquentAuditRecordRepository;
use Yammi\AuditLog\Infrastructure\Redaction\ConfigValueRedactor;
use Yammi\AuditLog\Infrastructure\Tenancy\NullTenantResolver;
use Yammi\AuditLog\Tests\TestCase;

final class BindingsTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string, class-string}>
     */
    public static function bindings(): iterable
    {
        yield 'read-model query' => [AuditLogQuery::class, EloquentAuditLogQuery::class];
        yield 'read-model repository' => [AuditRecordRepository::class, EloquentAuditRecordRepository::class];
        yield 'capture actor resolver' => [ActorResolver::class, ActorResolverChain::class];
        yield 'capture redactor' => [ValueRedactor::class, ConfigValueRedactor::class];
        yield 'capture pipeline' => [RecordChangePipeline::class, RecordChangePipeline::class];
        yield 'alerting channels' => [AlertChannels::class, AlertChannels::class];
        yield 'alerting anomaly scanner' => [AnomalyScanner::class, AnomalyScanner::class];
        yield 'integrity writer' => [AuditRowWriter::class, AuditRowWriter::class];
        yield 'integrity signer' => [IntegritySigner::class, IntegritySigner::class];
        yield 'tenancy resolver' => [TenantResolver::class, NullTenantResolver::class];
    }

    /**
     * @dataProvider bindings
     *
     * @param  class-string  $abstract
     * @param  class-string  $concrete
     */
    public function test_each_registrar_wires_its_services(string $abstract, string $concrete): void
    {
        $this->assertInstanceOf($concrete, $this->app->make($abstract));
    }
}
