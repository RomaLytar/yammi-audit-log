<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Correlation;

/**
 * Extracts the 16-byte trace id from a W3C `traceparent` header
 * (version-traceid-parentid-flags). Returns null for a missing, malformed or
 * all-zero (invalid) trace id. Trailing fields from future versions are ignored.
 *
 * @internal
 */
final class TraceParentParser
{
    private const INVALID_TRACE_ID = '00000000000000000000000000000000';

    public function traceId(?string $header): ?string
    {
        if ($header === null) {
            return null;
        }

        if (preg_match('/^[0-9a-f]{2}-([0-9a-f]{32})-[0-9a-f]{16}-[0-9a-f]{2}/i', trim($header), $matches) !== 1) {
            return null;
        }

        $traceId = strtolower($matches[1]);

        return $traceId === self::INVALID_TRACE_ID ? null : $traceId;
    }
}
