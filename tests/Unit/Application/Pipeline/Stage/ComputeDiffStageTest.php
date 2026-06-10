<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Pipeline\Stage;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\ChangeData;
use Yammi\AuditLog\Application\Pipeline\RecordChangeContext;
use Yammi\AuditLog\Application\Pipeline\Stage\ComputeDiffStage;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Tests\Support\StripKeysRedactor;

final class ComputeDiffStageTest extends TestCase
{
    public function test_it_redacts_changed_secret_values_and_drops_unchanged_fields(): void
    {
        $stage = new ComputeDiffStage(new StripKeysRedactor(['password']));

        $context = $stage(RecordChangeContext::start(new ChangeData(
            auditableType: 'App\\Models\\User',
            auditableId: '1',
            event: ChangeType::Updated,
            before: ['password' => 'old-secret', 'name' => 'Jane'],
            after: ['password' => 'new-secret', 'name' => 'Jane'],
        )));

        $password = $context->diff->field('password');
        $this->assertNotNull($password);
        $this->assertSame('[redacted]', $password->old);
        $this->assertSame('[redacted]', $password->new);
        $this->assertFalse($context->diff->has('name'));
    }

    public function test_an_unchanged_secret_does_not_appear_in_the_diff(): void
    {
        $stage = new ComputeDiffStage(new StripKeysRedactor(['password']));

        $context = $stage(RecordChangeContext::start(new ChangeData(
            auditableType: 'App\\Models\\User',
            auditableId: '1',
            event: ChangeType::Updated,
            before: ['password' => 'same', 'status' => 'active'],
            after: ['password' => 'same', 'status' => 'blocked'],
        )));

        $this->assertFalse($context->diff->has('password'));
        $this->assertTrue($context->diff->has('status'));
        $this->assertSame('blocked', $context->diff->field('status')?->new);
    }

    public function test_a_changed_secret_is_kept_but_its_values_are_redacted(): void
    {
        $stage = new ComputeDiffStage(new StripKeysRedactor(['password']));

        $context = $stage(RecordChangeContext::start(new ChangeData(
            auditableType: 'App\\Models\\User',
            auditableId: '1',
            event: ChangeType::Updated,
            before: ['password' => 'old-secret'],
            after: ['password' => 'new-secret'],
        )));

        $this->assertTrue($context->diff->has('password'));
        $this->assertSame('[redacted]', $context->diff->field('password')?->old);
        $this->assertSame('[redacted]', $context->diff->field('password')?->new);
    }
}
