<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Integrity;

use OpenSSLAsymmetricKey;

/**
 * Signs and verifies integrity digests with the host's asymmetric key pair
 * (RSA/EC, SHA-256). The chain hash catches edits to stored rows; a signed
 * digest additionally proves the chain head and record count at a point in
 * time, so deleting whole segments — or the entire table — is detectable and
 * an archived digest can be verified independently of the database. Keys may
 * be inline PEM or a readable file path. No key = unsigned (still recorded).
 *
 * @internal
 */
final class IntegritySigner
{
    public function __construct(
        private readonly ?string $privateKey = null,
        private readonly ?string $publicKey = null,
    ) {}

    public function algorithm(): string
    {
        return 'sha256';
    }

    public function canSign(): bool
    {
        return $this->resolvePrivate() !== null;
    }

    public function sign(string $payload): ?string
    {
        $key = $this->resolvePrivate();

        if ($key === null) {
            return null;
        }

        $signature = '';

        if (openssl_sign($payload, $signature, $key, OPENSSL_ALGO_SHA256) !== true) {
            return null;
        }

        return base64_encode($signature);
    }

    public function verify(string $payload, string $signature): bool
    {
        $key = $this->resolvePublic();

        if ($key === null) {
            return false;
        }

        $decoded = base64_decode($signature, true);

        if ($decoded === false) {
            return false;
        }

        return openssl_verify($payload, $decoded, $key, OPENSSL_ALGO_SHA256) === 1;
    }

    private function resolvePrivate(): ?OpenSSLAsymmetricKey
    {
        $material = $this->material($this->privateKey);

        if ($material === null) {
            return null;
        }

        $key = openssl_pkey_get_private($material);

        return $key === false ? null : $key;
    }

    private function resolvePublic(): ?OpenSSLAsymmetricKey
    {
        $material = $this->material($this->publicKey);

        if ($material === null) {
            return null;
        }

        $key = openssl_pkey_get_public($material);

        return $key === false ? null : $key;
    }

    private function material(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        if (str_contains($value, 'BEGIN')) {
            return $value;
        }

        if (is_file($value) && is_readable($value)) {
            $contents = file_get_contents($value);

            return $contents === false ? null : $contents;
        }

        return null;
    }
}
