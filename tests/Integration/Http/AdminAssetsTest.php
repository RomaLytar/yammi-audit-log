<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Tests\TestCase;

final class AdminAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_references_only_locally_served_assets(): void
    {
        $response = $this->get('audit-log');

        $response->assertOk();
        $response->assertSee('audit-log/assets/dashboard.css', false);
        $response->assertSee('audit-log/assets/lucide.js', false);
        $response->assertSee('audit-log/assets/inter-latin.woff2', false);
    }

    public function test_no_external_cdn_is_referenced(): void
    {
        $response = $this->get('audit-log');

        $response->assertDontSee('cdn.tailwindcss.com', false);
        $response->assertDontSee('unpkg.com', false);
        $response->assertDontSee('jsdelivr.net', false);
        $response->assertDontSee('rsms.me', false);
        $response->assertDontSee('lucide@latest', false);
    }

    public function test_the_asset_route_serves_the_vendored_files(): void
    {
        $css = $this->get('audit-log/assets/dashboard.css');
        $css->assertOk();
        $this->assertStringStartsWith('text/css', (string) $css->headers->get('Content-Type'));

        $js = $this->get('audit-log/assets/lucide.js');
        $js->assertOk();
        $this->assertStringStartsWith('text/javascript', (string) $js->headers->get('Content-Type'));

        $font = $this->get('audit-log/assets/inter-latin.woff2');
        $font->assertOk();
        $this->assertStringStartsWith('font/woff2', (string) $font->headers->get('Content-Type'));
    }

    public function test_an_unknown_asset_is_not_found(): void
    {
        $this->get('audit-log/assets/secrets.env')->assertNotFound();
    }
}
