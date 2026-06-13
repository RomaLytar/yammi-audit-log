<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel\Support;

/**
 * Renders a stored attribute value as a display string: null becomes a dash,
 * arrays are JSON-encoded, scalars are cast.
 *
 * @internal
 */
final class ValuePresenter
{
    /**
     * @param  scalar|array<array-key, mixed>|null  $value
     */
    public function present(string|int|float|bool|array|null $value): string
    {
        if ($value === null) {
            return '—';
        }

        return is_array($value) ? (string) json_encode($value) : (string) $value;
    }
}
