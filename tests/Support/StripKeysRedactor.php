<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use Yammi\AuditLog\Application\Contract\ValueRedactor;

final class StripKeysRedactor implements ValueRedactor
{
    /** @param list<string> $keys */
    public function __construct(
        private readonly array $keys,
    ) {}

    public function redact(array $values): array
    {
        foreach ($this->keys as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '[redacted]';
            }
        }

        return $values;
    }
}
