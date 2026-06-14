<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Stream\Driver;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Stream\Driver\DatadogLogsDriver;
use Yammi\AuditLog\Infrastructure\Stream\Driver\ElasticDriver;
use Yammi\AuditLog\Infrastructure\Stream\Driver\HttpStreamDriver;
use Yammi\AuditLog\Infrastructure\Stream\Driver\SplunkHecDriver;

final class StreamDriverTest extends TestCase
{
    public function test_splunk_wraps_the_event_and_sets_the_hec_token(): void
    {
        $driver = new SplunkHecDriver('https://splunk.test/x', 'tok', 'svc', ['X-Proxy' => '1']);

        $this->assertSame('splunk', $driver->name());
        $this->assertSame('https://splunk.test/x', $driver->endpoint());
        $this->assertSame(['Authorization' => 'Splunk tok', 'X-Proxy' => '1'], $driver->headers());
        $this->assertSame(['event' => ['e' => 1], 'sourcetype' => 'audit', 'source' => 'svc'], $driver->envelope(['e' => 1]));
    }

    public function test_datadog_sets_the_api_key_and_flattens_the_event(): void
    {
        $driver = new DatadogLogsDriver('https://dd.test/x', 'key', 'svc');

        $this->assertSame('datadog', $driver->name());
        $this->assertSame(['DD-API-KEY' => 'key'], $driver->headers());
        $this->assertSame(['ddsource' => 'audit-log', 'service' => 'svc', 'e' => 1], $driver->envelope(['e' => 1]));
    }

    public function test_elastic_sets_an_api_key_only_when_present(): void
    {
        $with = new ElasticDriver('https://es.test/i/_doc', 'apikey');
        $without = new ElasticDriver('https://es.test/i/_doc', null, ['X-Tenant' => 'acme']);

        $this->assertSame('elastic', $with->name());
        $this->assertSame(['Authorization' => 'ApiKey apikey'], $with->headers());
        $this->assertSame(['e' => 1], $with->envelope(['e' => 1]));
        $this->assertSame(['X-Tenant' => 'acme'], $without->headers());
    }

    public function test_http_adds_a_bearer_token_and_passes_the_event_through(): void
    {
        $driver = new HttpStreamDriver('https://sink.test/x', 'tok', ['X-Custom' => 'y']);

        $this->assertSame('http', $driver->name());
        $this->assertSame('https://sink.test/x', $driver->endpoint());
        $this->assertSame(['X-Custom' => 'y', 'Authorization' => 'Bearer tok'], $driver->headers());
        $this->assertSame(['e' => 1], $driver->envelope(['e' => 1]));
        $this->assertSame([], (new HttpStreamDriver('https://sink.test/x'))->headers());
    }
}
