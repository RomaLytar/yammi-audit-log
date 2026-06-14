<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Pipeline;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\Audit\ChangeData;
use Yammi\AuditLog\Application\Pipeline\RecordChangeContext;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;

final class RecordChangeContextTest extends TestCase
{
    public function test_start_creates_an_empty_context(): void
    {
        $context = RecordChangeContext::start($this->change());

        $this->assertTrue($context->diff->isEmpty());
        $this->assertNull($context->actor);
        $this->assertNull($context->origin);
        $this->assertTrue($context->labels->isEmpty());
        $this->assertFalse($context->isNoise);
    }

    public function test_each_with_returns_a_new_context_keeping_the_rest(): void
    {
        $start = RecordChangeContext::start($this->change());

        $diff = Diff::between(['a' => 1], ['a' => 2]);
        $labels = new LabelSnapshot(['user_id' => 'Jane']);
        $actor = Actor::user('1', 'Jane');
        $origin = Actor::command('app:sync');

        $context = $start
            ->withDiff($diff)
            ->withActor($actor, $origin)
            ->withLabels($labels)
            ->withNoise(true);

        $this->assertNotSame($start, $context);
        $this->assertSame($diff, $context->diff);
        $this->assertSame($actor, $context->actor);
        $this->assertSame($origin, $context->origin);
        $this->assertSame($labels, $context->labels);
        $this->assertTrue($context->isNoise);

        $this->assertTrue($start->diff->isEmpty());
        $this->assertNull($start->actor);
    }

    private function change(): ChangeData
    {
        return new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: ChangeType::Updated,
            before: ['a' => 1],
            after: ['a' => 2],
        );
    }
}
