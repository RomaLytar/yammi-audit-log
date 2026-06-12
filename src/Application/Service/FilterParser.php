<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service;

use DateInterval;
use DateTimeImmutable;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\DTO\AuditFilterData;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;

/**
 * Strict parser for filter input coming as a plain array (the facade): every
 * value is validated and normalised, anything unexpected is dropped — the
 * same guarantees the HTTP layer gives the dashboard. The date range always
 * comes back bounded: the current month when nothing is asked for, and never
 * wider than one year, so neither the dashboard nor the export can sweep the
 * whole table at once.
 *
 * @internal
 */
final class FilterParser
{
    private const MAX_TEXT = 255;

    private const MAX_RANGE_DAYS = 365;

    public function __construct(
        private readonly Clock $clock,
    ) {}

    /**
     * Recognised keys: model, event, actor_type, actor, id, from, to, search, page.
     *
     * @param  array<string, mixed>  $filters
     */
    public function fromArray(array $filters): AuditFilterData
    {
        $rawFrom = $this->date($filters['from'] ?? null);
        $rawTo = $this->date($filters['to'] ?? null);

        [$from, $to] = $this->boundedRange($rawFrom, $rawTo);

        return new AuditFilterData(
            type: $this->text($filters['model'] ?? null),
            event: $this->enumValue($filters['event'] ?? null, ChangeType::tryFrom(...)),
            actorType: $this->enumValue($filters['actor_type'] ?? null, ActorType::tryFrom(...)),
            actor: $this->text($filters['actor'] ?? null),
            from: $from,
            to: $to,
            page: $this->page($filters['page'] ?? null),
            search: $this->text($filters['search'] ?? null),
            defaultRange: $rawFrom === '' && $rawTo === '',
            auditableId: $this->identifier($filters['id'] ?? null),
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function boundedRange(string $from, string $to): array
    {
        $today = $this->clock->now();

        if ($from === '' && $to === '') {
            return [$today->format('Y-m-01'), $today->format('Y-m-d')];
        }

        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        if ($to === '') {
            $end = $this->day($from)->add(new DateInterval('P'.self::MAX_RANGE_DAYS.'D'));
            $to = ($end > $today ? $today : $end)->format('Y-m-d');
        }

        if ($from === '') {
            $from = $this->day($to)->sub(new DateInterval('P1M'))->format('Y-m-d');
        }

        $cap = $this->day($to)->sub(new DateInterval('P'.self::MAX_RANGE_DAYS.'D'));

        if ($this->day($from) < $cap) {
            $from = $cap->format('Y-m-d');
        }

        return [$from, $to];
    }

    private function day(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date === false ? $this->clock->now() : $date;
    }

    private function text(mixed $value): string
    {
        return is_string($value) ? mb_substr($value, 0, self::MAX_TEXT) : '';
    }

    private function identifier(mixed $value): string
    {
        if (is_int($value)) {
            $value = (string) $value;
        }

        return is_string($value) ? mb_substr(trim($value), 0, 64) : '';
    }

    /**
     * @param  callable(string): (\BackedEnum|null)  $resolve
     */
    private function enumValue(mixed $value, callable $resolve): string
    {
        return is_string($value) && $resolve($value) !== null ? $value : '';
    }

    private function date(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
    }

    private function page(mixed $value): int
    {
        return is_numeric($value) ? max(1, (int) $value) : 1;
    }
}
