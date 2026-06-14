<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel\Support;

use Illuminate\Support\Carbon;

/**
 * Formats a stored timestamp in the configured display timezone for the UI.
 *
 * @internal
 */
final class MomentFormatter
{
    public function __construct(
        private readonly ?string $timezone = null,
    ) {}

    public function format(string $moment, string $format = 'Y-m-d H:i'): string
    {
        $parsed = Carbon::parse($moment);

        if ($this->timezone !== null && $this->timezone !== '') {
            $parsed = $parsed->setTimezone($this->timezone);
        }

        return $parsed->format($format);
    }
}
