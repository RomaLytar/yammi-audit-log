<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract;

interface ValueRedactor
{
    /**
     * @param  array<string, scalar|array<array-key, mixed>|null>  $values
     * @return array<string, scalar|array<array-key, mixed>|null>
     */
    public function redact(array $values): array;
}
