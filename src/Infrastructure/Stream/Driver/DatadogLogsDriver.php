<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Stream\Driver;

use Yammi\AuditLog\Application\Contract\Stream\LogStreamDriver;

/** @internal */
final class DatadogLogsDriver implements LogStreamDriver
{
    /**
     * @param  array<string, string>  $extraHeaders
     */
    public function __construct(
        private readonly string $endpoint,
        private readonly string $token,
        private readonly string $source = 'audit-log',
        private readonly array $extraHeaders = [],
    ) {}

    public function name(): string
    {
        return 'datadog';
    }

    public function endpoint(): string
    {
        return $this->endpoint;
    }

    public function headers(): array
    {
        return ['DD-API-KEY' => $this->token] + $this->extraHeaders;
    }

    public function envelope(array $event): array
    {
        return ['ddsource' => 'audit-log', 'service' => $this->source] + $event;
    }
}
