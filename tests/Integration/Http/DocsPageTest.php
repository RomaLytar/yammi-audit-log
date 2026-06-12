<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Tests\TestCase;

final class DocsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_hub_links_to_the_documentation(): void
    {
        $this->get('audit-log/settings')
            ->assertOk()
            ->assertSee('Documentation')
            ->assertSee('audit-log/settings/docs');
    }

    public function test_the_docs_cover_every_feature(): void
    {
        $response = $this->get('audit-log/settings/docs');

        $response->assertOk();

        foreach ([
            'How capture works',
            'Choosing what gets audited',
            'Actors, origins and chains',
            'Time machine',
            'Record view',
            'GDPR subject reports',
            'Anomaly detection',
            'Multi-tenancy',
            'Request metadata',
            'Human-readable labels',
            'Noise diagnostics',
            'Finding things',
            'Export (CSV / JSON)',
            'JSON API',
            'Retention, archive, dedicated DB',
            'Tamper evidence',
            'Sensitive-change alerts',
            'Performance: async writes',
            'Embedding without this dashboard',
            'Secrets and redaction',
        ] as $title) {
            $response->assertSee($title);
        }

        $response->assertSee('audit-log:verify');
        $response->assertSee('data-al-doc-link', false);
        $response->assertSee('audit-log:archive');
        $response->assertSee('AUDIT_LOG_API_ENABLED');
    }
}
