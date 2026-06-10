<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Redaction;

use Yammi\AuditLog\Application\Contract\ValueRedactor;

final class ConfigValueRedactor implements ValueRedactor
{
    /**
     * @param  list<string>  $secretKeys
     */
    public function __construct(
        private readonly array $secretKeys,
        private readonly string $placeholder = '[redacted]',
    ) {}

    public function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($this->isSecret($key)) {
                $values[$key] = $this->placeholder;
            }
        }

        return $values;
    }

    private function isSecret(string $key): bool
    {
        $normalized = strtolower($key);

        foreach ($this->secretKeys as $needle) {
            if ($needle !== '' && str_contains($normalized, strtolower($needle))) {
                return true;
            }
        }

        return false;
    }
}
