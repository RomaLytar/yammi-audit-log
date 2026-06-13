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

final class PaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_pages_render_numbered_links_and_a_jump_input(): void
    {
        $this->seed(26);

        $response = $this->get('audit-log');

        $response->assertOk();
        $response->assertSee('Page 1 of 2');
        $response->assertSee('aria-label="Pagination"', false);
        $response->assertSee('page=2', false);          // a numbered / next link
        $response->assertSee('name="page"', false);     // the jump-to-page input
    }

    public function test_a_single_page_has_no_pagination_controls(): void
    {
        $this->seed(3);

        $this->get('audit-log')
            ->assertOk()
            ->assertDontSee('aria-label="Pagination"', false);
    }

    private function seed(int $count): void
    {
        $repository = $this->app->make(AuditRecordRepository::class);

        foreach (range(1, $count) as $i) {
            $repository->save(new AuditRecord(
                auditable: AuditableReference::to('App\\Models\\Order', (string) $i),
                event: ChangeType::Created,
                diff: Diff::between([], ['status' => 'new']),
                actor: Actor::system(),
                origin: null,
                labels: LabelSnapshot::empty(),
                occurredAt: new DateTimeImmutable('now'),
            ));
        }
    }
}
