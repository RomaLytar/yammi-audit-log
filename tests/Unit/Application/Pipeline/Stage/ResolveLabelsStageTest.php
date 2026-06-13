<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Pipeline\Stage;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\Audit\ChangeData;
use Yammi\AuditLog\Application\Pipeline\RecordChangeContext;
use Yammi\AuditLog\Application\Pipeline\Stage\ResolveLabelsStage;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\Support\StaticLabelResolver;

final class ResolveLabelsStageTest extends TestCase
{
    public function test_it_puts_the_resolved_labels_on_the_context(): void
    {
        $labels = new LabelSnapshot(['user_id' => 'Jane']);
        $stage = new ResolveLabelsStage(new StaticLabelResolver($labels));

        $context = $stage(RecordChangeContext::start(new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: ChangeType::Updated,
            before: [],
            after: [],
        )));

        $this->assertSame($labels, $context->labels);
    }
}
