<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Stream\Driver;

use Yammi\AuditLog\Application\Contract\LogStreamDriver;

/**
 * Generic HTTP/JSON sink for any system that accepts a POSTed JSON event with
 * caller-supplied auth headers (a bearer token and/or custom headers).
 *
 * @internal
 */
final class HttpStreamDriver implements LogStreamDriver
{
    /**
     * @param  array<string, string>  $extraHeaders
     */
    public function __construct(
        private readonly string $endpoint,
        private readonly ?string $token = null,
        private readonly array $extraHeaders = [],
    ) {}

    public function name(): string
    {
        return 'http';
    }

    public function endpoint(): string
    {
        return $this->endpoint;
    }

    public function headers(): array
    {
        $headers = $this->extraHeaders;

        if ($this->token !== null && $this->token !== '') {
            $headers['Authorization'] = 'Bearer '.$this->token;
        }

        return $headers;
    }

    public function envelope(array $event): array
    {
        return $event;
    }
}
