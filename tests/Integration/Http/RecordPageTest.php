<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\TestCase;

final class RecordPageTest extends TestCase
{
    use RefreshDatabase;

    private const CORRELATION = '11111111-1111-4111-8111-111111111111';

    public function test_the_record_page_shows_history_and_related_changes(): void
    {
        $this->seedGraph();

        $response = $this->get('audit-log/record?'.http_build_query([
            'type' => 'App\\Models\\Order',
            'id' => '42',
        ]));

        $response->assertOk();
        $response->assertSee('Order');
        $response->assertSee('History');
        $response->assertSee('fresh-status');
        $response->assertSee('Related changes');
        $response->assertSee('Invoice');
        $response->assertSee('same action');
        $response->assertSee('Shipment');
        $response->assertSee('references this record');
        $response->assertDontSee('Coupon');
    }

    public function test_the_page_requires_type_and_id(): void
    {
        $this->get('audit-log/record')->assertStatus(302);
    }

    public function test_an_unknown_record_shows_the_empty_state(): void
    {
        $this->get('audit-log/record?type=App%5CModels%5COrder&id=999')
            ->assertOk()
            ->assertSee('No recorded history');
    }

    public function test_the_dashboard_row_links_to_the_record_page(): void
    {
        $this->seedGraph();

        $this->get('audit-log?from=2026-01-01&to=2026-03-01')
            ->assertOk()
            ->assertSee('Record view')
            ->assertSee('audit-log/record?', false);
    }

    public function test_the_playground_executes_record_view(): void
    {
        $this->seedGraph();

        $response = $this->postJson(route('audit-log.playground.execute'), [
            'method' => 'recordView',
            'args' => [
                'auditable_type' => 'App\\Models\\Order',
                'auditable_id' => '42',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('result.auditableId', '42');
        $response->assertJsonPath('result.referenceField', 'order_id');
    }

    private function seedGraph(): void
    {
        $this->seedOne('App\\Models\\Order', '42', ['status' => 'fresh-status'], '2026-01-01 10:00:00', self::CORRELATION);
        $this->seedOne('App\\Models\\Invoice', '7', ['total' => 100], '2026-01-01 10:00:01', self::CORRELATION);
        $this->seedOne('App\\Models\\Shipment', '3', ['order_id' => 42], '2026-01-02 10:00:00');
        $this->seedOne('App\\Models\\Coupon', '9', ['code' => 'unrelated'], '2026-01-03 10:00:00');
    }

    /**
     * @param  array<string, scalar|null>  $after
     */
    private function seedOne(string $type, string $id, array $after, string $occurredAt, ?string $correlationId = null): void
    {
        $this->app->make(AuditRecordRepository::class)->save(new AuditRecord(
            auditable: AuditableReference::to($type, $id),
            event: ChangeType::Created,
            diff: Diff::between([], $after),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable($occurredAt),
            correlationId: $correlationId,
        ));
    }
}
