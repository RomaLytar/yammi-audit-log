<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Context;

use Throwable;
use Yammi\AuditLog\Application\Contract\Resolver\RequestContextResolver;

/** @internal */
final class HttpRequestContextResolver implements RequestContextResolver
{
    private const MAX_LENGTH = 255;

    public function __construct(
        private readonly RequestContextHolder $holder,
    ) {}

    public function resolve(): array
    {
        $request = $this->holder->current();

        if ($request === null) {
            return [];
        }

        try {
            return array_filter([
                'ip' => $this->cap($request->ip()),
                'url' => $this->cap($request->fullUrl()),
                'method' => $this->cap($request->method()),
                'user_agent' => $this->cap($request->userAgent()),
            ], static fn (string $value): bool => $value !== '');
        } catch (Throwable) {
            return [];
        }
    }

    private function cap(?string $value): string
    {
        return $value === null ? '' : mb_substr($value, 0, self::MAX_LENGTH);
    }
}
