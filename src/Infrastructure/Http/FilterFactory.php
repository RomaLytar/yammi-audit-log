<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http;

use Illuminate\Http\Request;
use Yammi\AuditLog\Application\DTO\AuditFilterData;

final class FilterFactory
{
    public function fromRequest(Request $request): AuditFilterData
    {
        return new AuditFilterData(
            type: $this->string($request->query('type')),
            event: $this->string($request->query('event')),
            actorType: $this->string($request->query('actor_type')),
            actor: $this->string($request->query('actor')),
            from: $this->string($request->query('from')),
            to: $this->string($request->query('to')),
            page: max(1, (int) $request->query('page', 1)),
        );
    }

    private function string(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
