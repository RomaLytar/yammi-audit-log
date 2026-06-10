<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Yammi\AuditLog\Application\DTO\AuditFilterData;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;

/**
 * Strict parser for the dashboard filter inputs: every value is validated and
 * normalised, and anything unexpected is dropped, so only known, well-formed
 * filters reach the query.
 *
 * @internal
 */
final class FilterFactory
{
    private const MAX_TEXT = 255;

    public function fromRequest(Request $request): AuditFilterData
    {
        return new AuditFilterData(
            type: $this->text($request->query('type')),
            event: $this->enumValue($request->query('event'), ChangeType::tryFrom(...)),
            actorType: $this->enumValue($request->query('actor_type'), ActorType::tryFrom(...)),
            actor: $this->text($request->query('actor')),
            from: $this->date($request->query('from')),
            to: $this->date($request->query('to')),
            page: $this->page($request->query('page')),
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
