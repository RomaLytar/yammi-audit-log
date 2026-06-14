<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Console\Support;

use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Console\Support\ArchiveDisk;

final class ArchiveDiskTest extends TestCase
{
    public function test_a_non_empty_option_wins(): void
    {
        $config = new Repository(['audit-log' => ['archive' => ['disk' => 'local']]]);

        $this->assertSame('s3', (new ArchiveDisk)->name('s3', $config));
    }

    public function test_it_falls_back_to_the_configured_disk(): void
    {
        $config = new Repository(['audit-log' => ['archive' => ['disk' => 'backups']]]);

        $this->assertSame('backups', (new ArchiveDisk)->name(null, $config));
        $this->assertSame('backups', (new ArchiveDisk)->name('', $config));
    }

    public function test_it_falls_back_to_local(): void
    {
        $this->assertSame('local', (new ArchiveDisk)->name(null, new Repository([])));
    }

    public function test_a_non_string_configured_value_falls_back_to_local(): void
    {
        $config = new Repository(['audit-log' => ['archive' => ['disk' => 123]]]);

        $this->assertSame('local', (new ArchiveDisk)->name(null, $config));
    }
}
