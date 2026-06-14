<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Alert\Channel;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;
use Yammi\AuditLog\Application\Contract\Alert\AlertChannel;
use Yammi\AuditLog\Application\DTO\Alert\AlertMessageData;

/**
 * Delivers an alert to a Slack incoming webhook as Block Kit: a header
 * with a severity emoji, the detail lines, an optional deep-link button
 * and a context line naming the sending application.
 *
 * @internal
 */
final class SlackAlertChannel implements AlertChannel
{
    private const TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $webhookUrl,
        private readonly ?string $sourceName = null,
    ) {}

    public function name(): string
    {
        return 'slack';
    }

    public function send(AlertMessageData $message): void
    {
        $response = $this->http
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(self::TIMEOUT_SECONDS)
            ->post($this->webhookUrl, $this->body($message));

        if ($response->status() < 200 || $response->status() >= 300) {
            throw new RuntimeException("Slack webhook returned HTTP {$response->status()}.");
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function body(AlertMessageData $message): array
    {
        $emoji = $message->kind === AlertMessageData::KIND_ANOMALY ? '📈' : '🔴';

        $blocks = [
            [
                'type' => 'header',
                'text' => ['type' => 'plain_text', 'text' => $emoji.'  '.$message->title],
            ],
            [
                'type' => 'section',
                'text' => ['type' => 'mrkdwn', 'text' => implode("\n", $message->lines)],
            ],
        ];

        if ($message->deepLink !== null) {
            $blocks[] = [
                'type' => 'actions',
                'elements' => [[
                    'type' => 'button',
                    'text' => ['type' => 'plain_text', 'text' => 'Open audit log'],
                    'url' => $message->deepLink,
                    'style' => 'primary',
                ]],
            ];
        }

        $parts = array_values(array_filter([$this->sourceName, 'at '.$message->occurredAt]));
        $blocks[] = [
            'type' => 'context',
            'elements' => [['type' => 'mrkdwn', 'text' => '_'.implode(' • ', $parts).'_']],
        ];

        return [
            'text' => $this->sourceName !== null && $this->sourceName !== ''
                ? "[{$this->sourceName}] {$message->title}"
                : $message->title,
            'blocks' => $blocks,
        ];
    }
}
