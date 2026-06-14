<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Integrity;

use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditDigestModel;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;

/**
 * Verifies the latest signed digest against the live chain: the signature must
 * check out and the attested chain head must still exist. A missing head means
 * the most recent segment (or the whole table) was deleted since signing.
 *
 * @internal
 */
final class DigestVerifier
{
    public function __construct(
        private readonly IntegritySigner $signer,
        private readonly DigestPayload $payload,
    ) {}

    public function verifyLatest(): ?DigestVerification
    {
        $digest = AuditDigestModel::query()->orderByDesc('id')->first();

        if (! $digest instanceof AuditDigestModel) {
            return null;
        }

        $head = $this->nullableString($digest->getAttribute('chain_head'));
        $signature = $this->nullableString($digest->getAttribute('signature'));
        $generatedAt = (string) $digest->getAttribute('generated_at');

        $payload = $this->payload->canonical(
            $head,
            (int) $digest->getAttribute('record_count'),
            $this->nullableString($digest->getAttribute('range_start')),
            $this->nullableString($digest->getAttribute('range_end')),
            $generatedAt,
        );

        $signatureValid = $signature !== null ? $this->signer->verify($payload, $signature) : null;

        $headPresent = $head === null
            || AuditRecordModel::query()->withoutGlobalScopes()->where('integrity_hash', $head)->exists();

        return new DigestVerification($generatedAt, $signature !== null, $signatureValid, $headPresent);
    }

    private function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }
}
