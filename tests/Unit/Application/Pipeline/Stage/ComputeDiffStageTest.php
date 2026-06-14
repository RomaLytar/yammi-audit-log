<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Pipeline\Stage;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\Audit\ChangeData;
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

    public function test_it_drops_ignored_attributes_from_the_diff(): void
    {
        $stage = new ComputeDiffStage(new StripKeysRedactor([]), ['updated_at']);

        $context = $stage(RecordChangeContext::start(new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: ChangeType::Updated,
            before: ['status' => 'pending', 'updated_at' => '2026-01-01 10:00:00'],
            after: ['status' => 'paid', 'updated_at' => '2026-01-01 11:00:00'],
        )));

        $this->assertTrue($context->diff->has('status'));
        $this->assertFalse($context->diff->has('updated_at'));
    }

    public function test_a_change_that_only_touches_ignored_attributes_is_flagged_as_noise(): void
    {
        $stage = new ComputeDiffStage(new StripKeysRedactor([]), ['created_at', 'updated_at']);

        $context = $stage(RecordChangeContext::start(new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: ChangeType::Updated,
            before: ['updated_at' => '2026-01-01 10:00:00'],
            after: ['updated_at' => '2026-01-01 11:00:00'],
        )));

        // Recorded, but flagged as noise and keeping the raw change for diagnosis.
        $this->assertTrue($context->isNoise);
        $this->assertFalse($context->diff->isEmpty());
        $this->assertTrue($context->diff->has('updated_at'));
    }

    public function test_a_truly_empty_change_is_not_noise(): void
    {
        $stage = new ComputeDiffStage(new StripKeysRedactor([]), ['updated_at']);

        $context = $stage(RecordChangeContext::start(new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: ChangeType::Updated,
            before: ['status' => 'paid'],
            after: ['status' => 'paid'],
        )));

        $this->assertFalse($context->isNoise);
        $this->assertTrue($context->diff->isEmpty());
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
