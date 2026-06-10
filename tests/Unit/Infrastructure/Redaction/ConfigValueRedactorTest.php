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

    public function test_it_redacts_secret_keys_nested_inside_array_values(): void
    {
        $redactor = new ConfigValueRedactor(['api_key', 'password']);

        $out = $redactor->redact([
            'settings' => [
                'api_key' => 'sk-live-123',
                'theme' => 'dark',
                'smtp' => ['password' => 'plain', 'host' => 'mail.test'],
            ],
        ]);

        $this->assertSame(
            [
                'settings' => [
                    'api_key' => '[redacted]',
                    'theme' => 'dark',
                    'smtp' => ['password' => '[redacted]', 'host' => 'mail.test'],
                ],
            ],
            $out,
        );
    }

    public function test_a_secret_key_holding_an_array_is_replaced_entirely(): void
    {
        $redactor = new ConfigValueRedactor(['credentials']);

        $out = $redactor->redact(['credentials' => ['user' => 'a', 'pass' => 'b']]);

        $this->assertSame(['credentials' => '[redacted]'], $out);
    }

    public function test_lists_inside_values_keep_their_safe_entries(): void
    {
        $redactor = new ConfigValueRedactor(['token']);

        $out = $redactor->redact(['meta' => [['token' => 'x', 'name' => 'a'], ['name' => 'b']]]);

        $this->assertSame(['meta' => [['token' => '[redacted]', 'name' => 'a'], ['name' => 'b']]], $out);
    }
}
