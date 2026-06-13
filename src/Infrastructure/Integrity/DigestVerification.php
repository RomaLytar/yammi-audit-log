<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Integrity;

/** @internal */
final class DigestVerification
{
    public function __construct(
        public readonly string $generatedAt,
        public readonly bool $signed,
        public readonly ?bool $signatureValid,
        public readonly bool $headPresent,
    ) {}

    public function passed(): bool
    {
        return $this->signatureValid !== false && $this->headPresent;
    }
}
