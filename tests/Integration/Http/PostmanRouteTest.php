<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Tests\TestCase;

final class PostmanRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_downloads_the_collection_when_the_api_is_on(): void
    {
        config()->set('audit-log.api.enabled', true);

        $response = $this->get('audit-log/postman');

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename="audit-log-api.postman.json"');
        $this->assertStringContainsString('v2.1.0', (string) $response->getContent());
        $this->assertStringContainsString('audit-log/api/changes', (string) $response->getContent());
    }

    public function test_it_is_not_available_when_the_api_is_off(): void
    {
        config()->set('audit-log.api.enabled', false);

        $this->get('audit-log/postman')->assertNotFound();
    }

    public function test_it_is_not_available_when_the_export_is_disabled(): void
    {
        config()->set('audit-log.api.enabled', true);
        config()->set('audit-log.api.postman', false);

        $this->get('audit-log/postman')->assertNotFound();
    }

    public function test_the_docs_page_shows_a_postman_button_when_the_api_is_on(): void
    {
        config()->set('audit-log.api.enabled', true);

        $this->get('audit-log/settings/docs')->assertOk()->assertSee('Download Postman collection');
    }

    public function test_the_docs_page_hides_the_postman_button_when_the_api_is_off(): void
    {
        config()->set('audit-log.api.enabled', false);

        $this->get('audit-log/settings/docs')->assertOk()->assertDontSee('Download Postman collection');
    }
}
