<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Stream;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Psr\Log\LoggerInterface;
use Throwable;
use Yammi\AuditLog\Application\Contract\LogStreamDriver;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Infrastructure\Job\StreamAuditEventJob;

/**
 * Ships every recorded change to a configured SIEM/log sink. The event is
 * normalized once here and wrapped by the driver's envelope; delivery is
 * queued so it never blocks the write path. Fail-soft: a streaming problem
 * is logged and swallowed, exactly like alerting.
 *
 * @internal
 */
final class ChangeStreamer
{
    public function __construct(
        private readonly ?LogStreamDriver $driver,
        private readonly BusDispatcher $bus,
        private readonly LoggerInterface $logger,
        private readonly ?string $queue = null,
    ) {}

    public function push(AuditRecord $record): void
    {
        $driver = $this->driver;

        if ($driver === null) {
            return;
        }

        try {
            $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'] + $driver->headers();
            $body = json_encode($driver->envelope($this->event(TimelineEntryData::fromRecord($record))), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($body === false) {
                return;
            }

            $job = new StreamAuditEventJob($driver->endpoint(), $headers, $body);

            if ($this->queue !== null && $this->queue !== '') {
                $job->onQueue($this->queue);
            }

            $this->bus->dispatch($job);
        } catch (Throwable $exception) {
            $this->logger->error(
                'Audit log stream dispatch failed: '.$exception->getMessage(),
                ['exception' => $exception],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function event(TimelineEntryData $entry): array
    {
        return [
            'event' => $entry->event,
            'model' => $entry->auditableType,
            'id' => $entry->auditableId,
            'actor' => $entry->actorLabel,
            'actor_type' => $entry->actorType,
            'origin' => $entry->originLabel,
            'changes' => $entry->changes,
            'reason' => $entry->reason,
            'labels' => $entry->labels,
            'correlation_id' => $entry->correlationId,
            'is_noise' => $entry->isNoise,
            'occurred_at' => $entry->occurredAt,
        ];
    }
}
