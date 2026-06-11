<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action;

use DateInterval;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;

/** @internal */
final class PruneAuditLogAction
{
    public const DEFAULT_DAYS = 180;

    public const MIN_DAYS = 7;

    public const MAX_DAYS = 9999;

    public function __construct(
        private readonly AuditRecordRepository $repository,
        private readonly Clock $clock,
    ) {}

    /**
     * Delete records older than the retention window, clamped into
     * [MIN_DAYS, MAX_DAYS]. A window of zero (or less) means "keep forever"
     * and prunes nothing.
     *
     * @return int number of deleted records
     */
    public function __invoke(int $retentionDays): int
    {
        if ($retentionDays <= 0) {
            return 0;
        }

        $cutoff = $this->clock->now()->sub(new DateInterval('P'.self::clampDays($retentionDays).'D'));

        return $this->repository->deleteOlderThan($cutoff);
    }

    public static function clampDays(int $days): int
    {
        return min(self::MAX_DAYS, max(self::MIN_DAYS, $days));
    }
}
