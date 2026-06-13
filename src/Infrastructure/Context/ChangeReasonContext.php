<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Context;

/**
 * Holds the reason for the change(s) currently in flight. Pushed when the host
 * opens a reasoned scope (AuditLog::withReason) and popped when it closes; a
 * stack supports nested scopes.
 *
 * @internal
 */
final class ChangeReasonContext
{
    /** @var list<string> */
    private array $stack = [];

    public function push(string $reason): void
    {
        $this->stack[] = $reason;
    }

    public function pop(): void
    {
        array_pop($this->stack);
    }

    public function current(): ?string
    {
        $key = array_key_last($this->stack);

        if ($key === null) {
            return null;
        }

        $reason = $this->stack[$key];

        return $reason === '' ? null : $reason;
    }
}
