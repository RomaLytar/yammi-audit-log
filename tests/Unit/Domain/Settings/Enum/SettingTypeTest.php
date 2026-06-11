<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Domain\Settings\Enum;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Settings\Enum\SettingType;

final class SettingTypeTest extends TestCase
{
    public function test_boolean_cast_and_serialize(): void
    {
        $this->assertTrue(SettingType::Boolean->cast('1'));
        $this->assertTrue(SettingType::Boolean->cast('true'));
        $this->assertFalse(SettingType::Boolean->cast('0'));
        $this->assertFalse(SettingType::Boolean->cast(''));

        $this->assertSame('1', SettingType::Boolean->serialize(true));
        $this->assertSame('0', SettingType::Boolean->serialize(false));
    }

    public function test_integer_cast_and_serialize(): void
    {
        $this->assertSame(180, SettingType::Integer->cast('180'));
        $this->assertSame(0, SettingType::Integer->cast('abc'));

        $this->assertSame('42', SettingType::Integer->serialize(42));
    }

    public function test_string_cast_and_serialize(): void
    {
        $this->assertSame('hello', SettingType::String->cast('hello'));
        $this->assertSame('hello', SettingType::String->serialize('hello'));
    }

    public function test_csv_list_cast_trims_and_drops_empties(): void
    {
        $this->assertSame(
            ['password', 'token', 'api_key'],
            SettingType::CsvList->cast(' password,token , ,api_key,'),
        );
        $this->assertSame([], SettingType::CsvList->cast(''));
    }

    public function test_csv_list_serialize_joins_with_commas(): void
    {
        $this->assertSame('password, token', SettingType::CsvList->serialize(['password', 'token']));
        $this->assertSame('', SettingType::CsvList->serialize([]));
    }
}
