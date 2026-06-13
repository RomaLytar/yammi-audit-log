<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Alert;

use Psr\Log\LoggerInterface;
use Throwable;
use Yammi\AuditLog\Application\Contract\Alert\AlertChannel;
use Yammi\AuditLog\Application\DTO\Alert\AlertMessageData;

/**
 * Fans one alert out to every configured channel, fail-soft per channel:
 * a dead Slack webhook must neither block the generic webhook nor the
 * write path the alert piggybacks on.
 *
 * @internal
 */
final class AlertChannels
{
    /**
     * @param  list<AlertChannel>  $channels
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly array $channels = [],
    ) {}

    public function dispatch(AlertMessageData $message): void
    {
        foreach ($this->channels as $channel) {
            try {
                $channel->send($message);
            } catch (Throwable $exception) {
                $this->logger->error(
                    "Audit alert delivery via {$channel->name()} failed: {$exception->getMessage()}",
                    ['channel' => $channel->name()],
                );
            }
        }
    }
}
