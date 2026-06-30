<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Capture;

use DateTimeImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Psr\Log\LoggerInterface;
use Throwable;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\DTO\Audit\CaptureFailureData;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Events\AuditCaptureFailed;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditCaptureFailureModel;

/**
 * Makes fail-open capture failures visible: it logs them, records each to its
 * own table for the dashboard health view, and fires AuditCaptureFailed.
 * Persisting is best-effort and never rethrows, so a broken audit store cannot
 * crash the host write it was auditing.
 *
 * @internal
 */
final class CaptureFailureLog implements CaptureFailureReporter
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly LoggerInterface $logger,
        private readonly Clock $clock,
    ) {}

    public function record(?Model $model, ?ChangeType $event, Throwable $exception): void
    {
        $type = $model?->getMorphClass();
        $eventValue = $event?->value;

        $this->logger->error('Audit capture failed: '.$exception->getMessage(), ['exception' => $exception]);

        try {
            AuditCaptureFailureModel::query()->create([
                'auditable_type' => $type,
                'event' => $eventValue,
                'exception' => $exception::class,
                'message' => mb_substr($exception->getMessage(), 0, 1000),
                'occurred_at' => $this->clock->now()->format('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            // Best-effort: if the audit store itself is unreachable the log line
            // and the event still fire; the host write is never interrupted.
        }

        try {
            $this->events->dispatch(new AuditCaptureFailed($type, $eventValue, $exception));
        } catch (Throwable) {
            // A throwing listener must not bubble into the host write either.
        }
    }

    /**
     * Capture-health snapshot for the dashboard: how many changes failed to be
     * audited since the cutoff, and the most recent failures. Fail-soft, so the
     * page still renders when the audit store is unreachable.
     *
     * @return array{count: int, recent: list<CaptureFailureData>}
     */
    public function health(DateTimeImmutable $cutoff, int $limit = 10): array
    {
        try {
            return ['count' => $this->countSince($cutoff), 'recent' => $this->recent($limit)];
        } catch (Throwable) {
            return ['count' => 0, 'recent' => []];
        }
    }

    public function countSince(DateTimeImmutable $cutoff): int
    {
        return AuditCaptureFailureModel::query()
            ->where('occurred_at', '>=', $cutoff->format('Y-m-d H:i:s'))
            ->count();
    }

    /**
     * @return list<CaptureFailureData>
     */
    public function recent(int $limit = 20): array
    {
        $rows = AuditCaptureFailureModel::query()
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(max(1, $limit))
            ->get();

        $failures = [];

        foreach ($rows as $row) {
            $failures[] = new CaptureFailureData(
                auditableType: $this->nullableString($row->getAttribute('auditable_type')),
                event: $this->nullableString($row->getAttribute('event')),
                exception: (string) $row->getAttribute('exception'),
                message: (string) $row->getAttribute('message'),
                occurredAt: (string) $row->getAttribute('occurred_at'),
            );
        }

        return $failures;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
