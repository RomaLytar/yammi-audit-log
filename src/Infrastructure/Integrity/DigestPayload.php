<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Integrity;

/**
 * The canonical, deterministic byte string that gets signed and re-derived for
 * verification. Both the recorder and the verifier build it the same way so a
 * stored signature can be checked against freshly read columns.
 *
 * @internal
 */
final class DigestPayload
{
    public function canonical(?string $head, int $count, ?string $start, ?string $end, string $generatedAt): string
    {
        $json = json_encode([
            'chain_head' => $head,
            'record_count' => $count,
            'range_start' => $start,
            'range_end' => $end,
            'generated_at' => $generatedAt,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? '' : $json;
    }
}
