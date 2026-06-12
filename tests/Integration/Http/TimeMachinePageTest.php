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
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Tests\TestCase;

final class TimeMachinePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_renders_the_empty_form(): void
    {
        $this->get('audit-log/time-machine')
            ->assertOk()
            ->assertSee('Time machine')
            ->assertSee('Reconstruct')
            ->assertSee('Pick a model, an id and a date');
    }

    public function test_the_nav_links_to_the_time_machine(): void
    {
        $this->get('audit-log')
            ->assertOk()
            ->assertSee('audit-log/time-machine');
    }

    public function test_the_state_is_reconstructed_at_a_date(): void
    {
        $this->seedHistory();

        $response = $this->get('audit-log/time-machine?'.http_build_query([
            'type' => 'App\\Models\\Order',
            'id' => '42',
            'at' => '2026-01-15',
        ]));

        $response->assertOk();
        $response->assertSee('existed');
        $response->assertSee('fresh-status');
        $response->assertDontSee('paid-status');
    }

    public function test_after_the_update_the_new_value_wins(): void
    {
        $this->seedHistory();

        $response = $this->get('audit-log/time-machine?'.http_build_query([
            'type' => 'App\\Models\\Order',
            'id' => '42',
            'at' => '2026-03-01',
        ]));

        $response->assertOk();
        $response->assertSee('paid-status');
        $response->assertSee('Applied history');
        $response->assertSee('2 changes folded into this state');
    }

    public function test_before_creation_there_is_no_history(): void
    {
        $this->seedHistory();

        $response = $this->get('audit-log/time-machine?'.http_build_query([
            'type' => 'App\\Models\\Order',
            'id' => '42',
            'at' => '2025-12-01',
        ]));

        $response->assertOk();
        $response->assertSee('No recorded history');
    }

    public function test_a_deleted_record_keeps_its_last_known_values(): void
    {
        $this->seedHistory();
        $this->seedRecord(ChangeType::Deleted, ['status' => 'paid-status'], [], '2026-04-01 09:00:00');

        $response = $this->get('audit-log/time-machine?'.http_build_query([
            'type' => 'App\\Models\\Order',
            'id' => '42',
            'at' => '2026-04-10',
        ]));

        $response->assertOk();
        $response->assertSee('deleted at this moment');
        $response->assertSee('paid-status');
    }

    public function test_the_history_stops_at_the_chosen_moment(): void
    {
        $this->seedHistory();

        $response = $this->get('audit-log/time-machine?'.http_build_query([
            'type' => 'App\\Models\\Order',
            'id' => '42',
            'at' => '2026-01-15',
        ]));

        $response->assertOk();
        $response->assertSee('1 change folded into this state');
        $response->assertDontSee('paid-status');
    }

    public function test_the_record_link_filters_the_dashboard_to_one_record(): void
    {
        $this->seedHistory();
        $this->seedRecord(ChangeType::Created, [], ['status' => 'other-record-marker'], '2026-01-05 10:00:00', '43');

        $response = $this->get('audit-log/time-machine?'.http_build_query([
            'type' => 'App\\Models\\Order',
            'id' => '42',
            'at' => '2026-03-01',
        ]));

        $response->assertOk();
        $response->assertSee('id=42', false);

        $dashboard = $this->get('audit-log?'.http_build_query([
            'type' => 'App\\Models\\Order',
            'id' => '42',
            'from' => '2026-01-01',
            'to' => '2026-03-01',
        ]));

        $dashboard->assertOk();
        $dashboard->assertSee('fresh-status');
        $dashboard->assertDontSee('other-record-marker');
    }

    public function test_the_facade_accepts_the_id_filter(): void
    {
        $this->seedHistory();
        $this->seedRecord(ChangeType::Created, [], ['status' => 'other-record-marker'], '2026-01-05 10:00:00', '43');

        $list = AuditLog::changes([
            'id' => '42',
            'from' => '2026-01-01',
            'to' => '2026-03-01',
        ]);

        $this->assertSame(2, $list->total);
    }

    public function test_the_dashboard_row_links_to_the_time_machine(): void
    {
        $this->seedHistory();

        $this->get('audit-log?from=2026-01-01&to=2026-04-30')
            ->assertOk()
            ->assertSee('State at this moment');
    }

    private function seedHistory(): void
    {
        $this->seedRecord(ChangeType::Created, [], ['status' => 'fresh-status', 'total' => 100], '2026-01-01 10:00:00');
        $this->seedRecord(ChangeType::Updated, ['status' => 'fresh-status'], ['status' => 'paid-status'], '2026-02-01 10:00:00');
    }

    /**
     * @param  array<string, scalar|null>  $before
     * @param  array<string, scalar|null>  $after
     */
    private function seedRecord(ChangeType $event, array $before, array $after, string $occurredAt, string $id = '42'): void
    {
        $this->app->make(AuditRecordRepository::class)->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', $id),
            event: $event,
            diff: Diff::between($before, $after),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable($occurredAt),
        ));
    }
}
