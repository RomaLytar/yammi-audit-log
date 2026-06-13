<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Stream\Driver;

use Yammi\AuditLog\Application\Contract\LogStreamDriver;

/** @internal */
final class ElasticDriver implements LogStreamDriver
{
    /**
     * @param  array<string, string>  $extraHeaders
     */
    public function __construct(
        private readonly string $endpoint,
        private readonly ?string $apiKey = null,
        private readonly array $extraHeaders = [],
    ) {}

    public function name(): string
    {
        return 'elastic';
    }

    public function endpoint(): string
    {
        return $this->endpoint;
    }

    public function headers(): array
    {
        $headers = $this->extraHeaders;

        if ($this->apiKey !== null && $this->apiKey !== '') {
            $headers['Authorization'] = 'ApiKey '.$this->apiKey;
        }

        return $headers;
    }

    public function envelope(array $event): array
    {
        return $event;
    }
}
