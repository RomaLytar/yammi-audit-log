<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Anomaly;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Throwable;
use Yammi\AuditLog\Application\Contract\AnomalyRule;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\DTO\Anomaly\AnomalyData;
use Yammi\AuditLog\Application\DTO\Anomaly\AnomalyWindow;
use Yammi\AuditLog\Application\DTO\Audit\TimelineEntryData;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Infrastructure\Persistence\Mapper\AuditRecordMapper;

/**
 * Scans a recent window of the audit log for suspicious patterns: a burst
 * of changes by one actor, a mass deletion, user activity in off hours.
 *
 * @internal
 */
final class AnomalyScanner
{
    private const OFF_HOURS_SAMPLE = 5000;

    private const RULE_SAMPLE = 5000;

    /**
     * @param  list<int>  $offHours  inclusive [from, to] hour range, wrapping midnight when from > to
     * @param  list<AnomalyRule>  $rules  host-defined detection-as-code rules
     */
    public function __construct(
        private readonly Clock $clock,
        private readonly int $rateThreshold = 200,
        private readonly int $deleteThreshold = 25,
        private readonly int $cascadeThreshold = 150,
        private readonly array $offHours = [],
        private readonly array $rules = [],
        private readonly AuditRecordMapper $mapper = new AuditRecordMapper,
    ) {}

    /**
     * @return list<AnomalyData>
     */
    public function scan(int $windowMinutes): array
    {
        $end = $this->clock->now();
        $start = $end->sub(new DateInterval('PT'.max(1, $windowMinutes).'M'));

        return array_merge(
            $this->burstFindings($start, $end, null, $this->rateThreshold, AnomalyData::RULE_RATE_SPIKE, 'changes'),
            $this->burstFindings($start, $end, ChangeType::Deleted, $this->deleteThreshold, AnomalyData::RULE_MASS_DELETE, 'deletions'),
            $this->cascadeFindings($start, $end),
            $this->offHoursFindings($start, $end),
            $this->customFindings($start, $end),
        );
    }

    /**
     * @return list<AnomalyData>
     */
    private function customFindings(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        if ($this->rules === []) {
            return [];
        }

        $entries = [];

        foreach ($this->windowQuery($start, $end)->orderBy('id')->limit(self::RULE_SAMPLE)->get() as $model) {
            $entries[] = TimelineEntryData::fromRecord($this->mapper->toDomain($model));
        }

        $window = new AnomalyWindow($start, $end);
        $findings = [];

        foreach ($this->rules as $rule) {
            try {
                foreach ($rule->evaluate($entries, $window) as $finding) {
                    $findings[] = $finding;
                }
            } catch (Throwable) {
            }
        }

        return $findings;
    }

    /**
     * @return list<AnomalyData>
     */
    private function burstFindings(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        ?ChangeType $event,
        int $threshold,
        string $rule,
        string $unit,
    ): array {
        if ($threshold <= 0) {
            return [];
        }

        $query = $this->windowQuery($start, $end)
            ->groupBy('actor_type', 'actor_id', 'actor_label')
            ->selectRaw('actor_type, actor_id, actor_label, count(*) as total')
            ->havingRaw('count(*) > ?', [$threshold]);

        if ($event !== null) {
            $query->where('event', $event->value);
        }

        $findings = [];

        foreach ($query->get() as $row) {
            $count = (int) $row->getAttribute('total');
            $label = $this->label($row->getAttribute('actor_label'), $row->getAttribute('actor_id'));

            $findings[] = $this->finding(
                $rule,
                (string) $row->getAttribute('actor_type'),
                $label,
                $count,
                $start,
                $end,
                sprintf('%d %s by %s in the last window (threshold %d).', $count, $unit, $label, $threshold),
            );
        }

        return $findings;
    }

    /**
     * One unit of work (a single correlation) that produced an unusually large
     * number of changes across many models, a possible write-amplification or
     * N+1-style cascade rather than a security event.
     *
     * @return list<AnomalyData>
     */
    private function cascadeFindings(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        if ($this->cascadeThreshold <= 0) {
            return [];
        }

        $rows = $this->windowQuery($start, $end)
            ->whereNotNull('correlation_id')
            ->groupBy('correlation_id')
            ->selectRaw('correlation_id, count(*) as total, count(distinct auditable_type) as models')
            ->havingRaw('count(*) > ?', [$this->cascadeThreshold])
            ->get();

        $findings = [];

        foreach ($rows as $row) {
            $total = (int) $row->getAttribute('total');
            $models = (int) $row->getAttribute('models');
            $chain = substr((string) $row->getAttribute('correlation_id'), 0, 8);

            $findings[] = $this->finding(
                AnomalyData::RULE_CASCADE,
                ActorType::System->value,
                'chain '.$chain,
                $total,
                $start,
                $end,
                sprintf(
                    'One action (chain %s) produced %d changes across %d model(s), over the threshold of %d. Possible write-amplification or N+1-style cascade.',
                    $chain,
                    $total,
                    $models,
                    $this->cascadeThreshold,
                ),
            );
        }

        return $findings;
    }

    /**
     * @return list<AnomalyData>
     */
    private function offHoursFindings(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        if (count($this->offHours) !== 2) {
            return [];
        }

        $rows = $this->windowQuery($start, $end)
            ->where('actor_type', ActorType::User->value)
            ->limit(self::OFF_HOURS_SAMPLE)
            ->get(['actor_id', 'actor_label', 'occurred_at']);

        $counts = [];
        $labels = [];

        foreach ($rows as $row) {
            $occurredAt = (string) $row->getAttribute('occurred_at');
            $hour = (int) substr($occurredAt, 11, 2);

            if (! $this->isOffHour($hour)) {
                continue;
            }

            $key = (string) $row->getAttribute('actor_id');
            $counts[$key] = ($counts[$key] ?? 0) + 1;
            $labels[$key] = $this->label($row->getAttribute('actor_label'), $row->getAttribute('actor_id'));
        }

        $findings = [];

        foreach ($counts as $key => $count) {
            $findings[] = $this->finding(
                AnomalyData::RULE_OFF_HOURS,
                ActorType::User->value,
                $labels[$key],
                $count,
                $start,
                $end,
                sprintf(
                    '%d user change(s) by %s between %02d:00 and %02d:59.',
                    $count,
                    $labels[$key],
                    $this->offHours[0],
                    $this->offHours[1],
                ),
            );
        }

        return $findings;
    }

    /**
     * @return Builder<AuditRecordModel>
     */
    private function windowQuery(DateTimeImmutable $start, DateTimeImmutable $end): Builder
    {
        return AuditRecordModel::query()
            ->where('occurred_at', '>=', $start->format('Y-m-d H:i:s'))
            ->where('occurred_at', '<=', $end->format('Y-m-d H:i:s'));
    }

    private function isOffHour(int $hour): bool
    {
        [$from, $to] = $this->offHours;

        return $from <= $to
            ? $hour >= $from && $hour <= $to
            : $hour >= $from || $hour <= $to;
    }

    private function label(mixed $label, mixed $identifier): string
    {
        if (is_string($label) && $label !== '') {
            return $label;
        }

        return is_scalar($identifier) ? '#'.$identifier : 'unknown';
    }

    private function finding(
        string $rule,
        string $actorType,
        string $label,
        int $count,
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        string $description,
    ): AnomalyData {
        return new AnomalyData(
            rule: $rule,
            actorType: $actorType,
            actorLabel: $label,
            count: $count,
            windowStart: $start->format(DateTimeInterface::ATOM),
            windowEnd: $end->format(DateTimeInterface::ATOM),
            description: $description,
            severity: match ($rule) {
                AnomalyData::RULE_MASS_DELETE, AnomalyData::RULE_RATE_SPIKE => AnomalyData::SEVERITY_HIGH,
                default => AnomalyData::SEVERITY_MEDIUM,
            },
        );
    }
}
