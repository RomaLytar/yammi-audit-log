<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Transfer;

/** @internal */
final class TransferRunResult
{
    private function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly int $rowsMoved,
    ) {}

    public static function success(int $rowsMoved): self
    {
        return new self(true, "Done. {$rowsMoved} row(s) moved.", $rowsMoved);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message, 0);
    }
}
