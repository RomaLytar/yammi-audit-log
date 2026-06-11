<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service;

use DateTimeImmutable;
use Yammi\AuditLog\Application\DTO\AuditFilterData;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;

/**
 * Strict parser for filter input coming as a plain array (the facade): every
 * value is validated and normalised, anything unexpected is dropped — the
 * same guarantees the HTTP layer gives the dashboard.
 *
 * @internal
 */
final class FilterParser
{
    private const MAX_TEXT = 255;

    /**
     * Recognised keys: model, event, actor_type, actor, from, to, search, page.
     *
     * @param  array<string, mixed>  $filters
     */
    public function fromArray(array $filters): AuditFilterData
    {
        return new AuditFilterData(
            type: $this->text($filters['model'] ?? null),
            event: $this->enumValue($filters['event'] ?? null, ChangeType::tryFrom(...)),
            actorType: $this->enumValue($filters['actor_type'] ?? null, ActorType::tryFrom(...)),
            actor: $this->text($filters['actor'] ?? null),
            from: $this->date($filters['from'] ?? null),
            to: $this->date($filters['to'] ?? null),
            page: $this->page($filters['page'] ?? null),
            search: $this->text($filters['search'] ?? null),
        );
    }

    private function text(mixed $value): string
    {
        return is_string($value) ? mb_substr($value, 0, self::MAX_TEXT) : '';
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
