<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Redaction;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Redaction\ConfigValueRedactor;

final class ConfigValueRedactorTest extends TestCase
{
    public function test_it_redacts_keys_matching_a_secret_pattern(): void
    {
        $redactor = new ConfigValueRedactor(['password', 'token']);

        $out = $redactor->redact([
            'password' => 'plain',
            'api_token' => 'abc',
            'name' => 'Jane',
        ]);

        $this->assertSame('[redacted]', $out['password']);
        $this->assertSame('[redacted]', $out['api_token']);
        $this->assertSame('Jane', $out['name']);
    }

    public function test_matching_is_case_insensitive(): void
    {
        $redactor = new ConfigValueRedactor(['secret']);

        $out = $redactor->redact(['MySecretKey' => 'x']);

        $this->assertSame('[redacted]', $out['MySecretKey']);
    }

    public function test_an_empty_secret_list_redacts_nothing(): void
    {
        $redactor = new ConfigValueRedactor([]);

        $this->assertSame(['a' => '1'], $redactor->redact(['a' => '1']));
    }

    public function test_a_custom_placeholder_is_used(): void
    {
        $redactor = new ConfigValueRedactor(['password'], '***');

        $this->assertSame(['password' => '***'], $redactor->redact(['password' => 'plain']));
    }
}
