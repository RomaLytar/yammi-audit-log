<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http;

use Illuminate\Http\Request;
use Yammi\AuditLog\Application\DTO\AuditFilterData;
use Yammi\AuditLog\Application\Service\FilterParser;

/**
 * Maps the dashboard query string onto the shared strict filter parser, so
 * HTTP input gets exactly the validation the facade applies to arrays.
 *
 * @internal
 */
final class FilterFactory
{
    public function __construct(
        private readonly FilterParser $parser,
    ) {}

    public function fromRequest(Request $request): AuditFilterData
    {
        return $this->parser->fromArray([
            'model' => $request->query('type'),
            'event' => $request->query('event'),
            'actor_type' => $request->query('actor_type'),
            'actor' => $request->query('actor'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'page' => $request->query('page'),
            'search' => $request->query('q'),
            'id' => $request->query('id'),
        ]);
    }
}
