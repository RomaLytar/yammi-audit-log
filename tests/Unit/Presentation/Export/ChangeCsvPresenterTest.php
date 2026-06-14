<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Presentation\Export;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\Audit\TimelineEntryData;
use Yammi\AuditLog\Presentation\Export\ChangeCsvPresenter;

final class ChangeCsvPresenterTest extends TestCase
{
    public function test_rows_align_with_the_headings(): void
    {
        $presenter = new ChangeCsvPresenter;

        $row = $presenter->row($this->entry());

        $this->assertSame(count($presenter->headings()), count($row));
        $this->assertSame('12', $row[0]);
        $this->assertSame('Order', $row[2]);
        $this->assertSame('updated', $row[5]);
        $this->assertSame('1', $row[10]);
        $this->assertSame('{"status":{"old":"a","new":"b"}}', $row[11]);
    }

    public function test_formula_prefixes_are_neutralised_against_csv_injection(): void
    {
        $entry = new TimelineEntryData(
            id: 1,
            auditableType: '=HYPERLINK("http://evil")',
            auditableId: '+1',
            event: 'updated',
            actorType: 'user',
            actorLabel: '@cmd',
            originLabel: '-2+3',
            changes: [],
            labels: [],
            occurredAt: '2026-01-01T10:00:00+00:00',
            correlationId: null,
        );

        $row = (new ChangeCsvPresenter)->row($entry);

        $this->assertSame("'=HYPERLINK(\"http://evil\")", $row[3]);
        $this->assertSame("'+1", $row[4]);
        $this->assertSame("'@cmd", $row[7]);
        $this->assertSame("'-2+3", $row[8]);
    }

    public function test_json_rows_keep_structured_changes(): void
    {
        $row = (new ChangeCsvPresenter)->jsonRow($this->entry());

        $this->assertSame(12, $row['id']);
        $this->assertSame(['status' => ['old' => 'a', 'new' => 'b']], $row['changes']);
        $this->assertTrue($row['is_noise']);
    }

    private function entry(): TimelineEntryData
    {
        return new TimelineEntryData(
            id: 12,
            auditableType: 'App\\Models\\Order',
            auditableId: '7',
            event: 'updated',
            actorType: 'user',
            actorLabel: 'Jane',
            originLabel: null,
            changes: ['status' => ['old' => 'a', 'new' => 'b']],
            labels: [],
            occurredAt: '2026-01-01T10:00:00+00:00',
            correlationId: null,
            isNoise: true,
        );
    }
}
