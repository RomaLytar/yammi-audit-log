<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Tests\TestCase;

final class AdminAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_assets_are_pinned_to_fixed_versions(): void
    {
        $response = $this->get('audit-log');

        $response->assertOk();
        $response->assertSee('https://cdn.tailwindcss.com/3.4.16', false);
        $response->assertSee('lucide@0.468.0', false);
        $response->assertSee('@fontsource-variable/inter@5.1.0', false);
    }

    public function test_the_icon_script_is_protected_by_subresource_integrity(): void
    {
        $response = $this->get('audit-log');

        $response->assertSee('integrity="sha384-', false);
        $response->assertSee('crossorigin="anonymous"', false);
    }

    public function test_no_floating_or_unversioned_third_party_assets_remain(): void
    {
        $response = $this->get('audit-log');

        $response->assertDontSee('lucide@latest', false);
        $response->assertDontSee('rsms.me', false);
        $response->assertDontSee('cdn.tailwindcss.com"', false);
    }
}
