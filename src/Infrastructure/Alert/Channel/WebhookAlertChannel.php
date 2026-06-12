<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Alert\Channel;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use RuntimeException;
use Yammi\AuditLog\Application\Contract\AlertChannel;
use Yammi\AuditLog\Application\DTO\AlertMessageData;

/**
 * Generic signed-webhook delivery for systems that speak plain JSON
 * (incident routers, n8n/Zapier, internal hubs). The body is signed with
 * HMAC-SHA256 so receivers can verify X-Audit-Log-Signature against the
 * raw body. One retry on 5xx (transient), none on 4xx (configuration).
 *
 * @internal
 */
final class WebhookAlertChannel implements AlertChannel
{
    private const TIMEOUT_SECONDS = 5;

    private const USER_AGENT = 'yammi-audit-log-webhook/1.0';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly ?string $secret = null,
        private readonly ?string $sourceName = null,
    ) {}

    public function name(): string
    {
        return 'webhook';
    }

    public function send(AlertMessageData $message): void
    {
        $rawBody = $this->encode($this->body($message));
        $headers = $this->headers($rawBody, $message->kind);

        $response = $this->post($rawBody, $headers);

        if ($response->status() >= 500) {
            $response = $this->post($rawBody, $headers);
        }

        if ($response->status() < 200 || $response->status() >= 300) {
            throw new RuntimeException("Webhook endpoint returned HTTP {$response->status()}.");
        }
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function post(string $rawBody, array $headers): Response
    {
        try {
            return $this->http
                ->withHeaders($headers)
                ->timeout(self::TIMEOUT_SECONDS)
                ->withBody($rawBody, 'application/json')
                ->post($this->url);
        } catch (ConnectionException $exception) {
            throw new RuntimeException("Webhook endpoint unreachable: {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function body(AlertMessageData $message): array
    {
        $body = [
            'event' => 'audit.'.$message->kind,
            'title' => $message->title,
            'lines' => $message->lines,
            'context' => $message->context,
            'deep_link' => $message->deepLink,
            'timestamp' => $message->occurredAt,
        ];

        if ($this->sourceName !== null && $this->sourceName !== '') {
            $body['source'] = $this->sourceName;
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function encode(array $body): string
    {
        $json = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException('Failed to encode the webhook payload as JSON.');
        }

        return $json;
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $rawBody, string $kind): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => self::USER_AGENT,
            'X-Audit-Log-Event' => 'audit.'.$kind,
        ];

        if ($this->secret !== null && $this->secret !== '') {
            $headers['X-Audit-Log-Signature'] = 'sha256='.hash_hmac('sha256', $rawBody, $this->secret);
        }

        return $headers;
    }
}
