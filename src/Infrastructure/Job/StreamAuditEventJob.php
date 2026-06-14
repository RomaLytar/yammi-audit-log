<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;

/**
 * Delivers one already-formatted audit event to a SIEM/log sink off the
 * request path. The event is fully built at capture time; only the HTTP POST
 * is deferred. One retry on 5xx (transient), none on 4xx (configuration).
 *
 * @internal
 */
final class StreamAuditEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    private const TIMEOUT_SECONDS = 5;

    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly string $endpoint,
        public readonly array $headers,
        public readonly string $body,
    ) {}

    public function handle(HttpFactory $http): void
    {
        $response = $this->post($http);

        if ($response->status() >= 500) {
            $response = $this->post($http);
        }

        if ($response->status() < 200 || $response->status() >= 300) {
            throw new RuntimeException("Audit log stream endpoint returned HTTP {$response->status()}.");
        }
    }

    private function post(HttpFactory $http): Response
    {
        try {
            return $http
                ->withHeaders($this->headers)
                ->timeout(self::TIMEOUT_SECONDS)
                ->withBody($this->body, 'application/json')
                ->post($this->endpoint);
        } catch (ConnectionException $exception) {
            throw new RuntimeException("Audit log stream endpoint unreachable: {$exception->getMessage()}", 0, $exception);
        }
    }
}
