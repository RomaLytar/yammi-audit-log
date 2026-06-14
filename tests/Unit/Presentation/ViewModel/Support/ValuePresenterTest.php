<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Presentation\ViewModel\Support;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Presentation\ViewModel\Support\ValuePresenter;

final class ValuePresenterTest extends TestCase
{
    public function test_null_becomes_a_dash(): void
    {
        $this->assertSame('—', (new ValuePresenter)->present(null));
    }

    public function test_an_array_is_json_encoded(): void
    {
        $this->assertSame('{"a":1}', (new ValuePresenter)->present(['a' => 1]));
    }

    public function test_scalars_are_cast_to_strings(): void
    {
        $presenter = new ValuePresenter;

        $this->assertSame('draft', $presenter->present('draft'));
        $this->assertSame('5', $presenter->present(5));
        $this->assertSame('1', $presenter->present(true));
    }
}
