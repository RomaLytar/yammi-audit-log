<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Integrity;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Integrity\IntegritySigner;

final class IntegritySignerTest extends TestCase
{
    public function test_it_signs_and_verifies_with_a_key_pair(): void
    {
        ['private' => $private, 'public' => $public] = $this->keyPair();

        $signer = new IntegritySigner($private, $public);
        $payload = '{"chain_head":"abc","record_count":3}';

        $this->assertTrue($signer->canSign());

        $signature = $signer->sign($payload);

        $this->assertNotNull($signature);
        $this->assertTrue($signer->verify($payload, $signature));
        $this->assertFalse($signer->verify('{"chain_head":"FORGED"}', $signature));
    }

    public function test_a_signature_does_not_verify_against_a_different_key(): void
    {
        ['private' => $private] = $this->keyPair();
        ['public' => $otherPublic] = $this->keyPair();

        $signature = (new IntegritySigner($private))->sign('payload');

        $this->assertNotNull($signature);
        $this->assertFalse((new IntegritySigner(null, $otherPublic))->verify('payload', $signature));
    }

    public function test_without_keys_it_cannot_sign_or_verify(): void
    {
        $signer = new IntegritySigner(null, null);

        $this->assertFalse($signer->canSign());
        $this->assertNull($signer->sign('payload'));
        $this->assertFalse($signer->verify('payload', 'c2ln'));
    }

    /**
     * @return array{private: string, public: string}
     */
    private function keyPair(): array
    {
        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

        if ($resource === false) {
            $this->markTestSkipped('OpenSSL key generation is unavailable in this environment.');
        }

        openssl_pkey_export($resource, $private);
        $details = openssl_pkey_get_details($resource);

        if (! is_array($details) || ! is_string($details['key'] ?? null)) {
            $this->markTestSkipped('OpenSSL key export is unavailable in this environment.');
        }

        return ['private' => (string) $private, 'public' => $details['key']];
    }
}
