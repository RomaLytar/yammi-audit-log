<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Action;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\ResetSettingsAction;
use Yammi\AuditLog\Application\Action\UpdateSettingsAction;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Tests\Support\InMemoryGeneralSettingRepository;

final class UpdateSettingsActionTest extends TestCase
{
    private InMemoryGeneralSettingRepository $repository;

    private UpdateSettingsAction $update;

    protected function setUp(): void
    {
        $this->repository = new InMemoryGeneralSettingRepository;
        $this->update = new UpdateSettingsAction($this->repository, new SettingRegistry);
    }

    public function test_known_settings_are_persisted_serialized(): void
    {
        ($this->update)(['retention_days' => 90, 'prune_schedule_enabled' => false]);

        $this->assertSame('90', $this->repository->stored['general']['retention_days']);
        $this->assertSame('0', $this->repository->stored['general']['prune_schedule_enabled']);
    }

    public function test_out_of_bounds_values_are_clamped_before_storage(): void
    {
        ($this->update)(['retention_days' => 1]);
        $this->assertSame('7', $this->repository->stored['general']['retention_days']);

        ($this->update)(['retention_days' => 99999]);
        $this->assertSame('9999', $this->repository->stored['general']['retention_days']);
    }

    public function test_unknown_keys_are_ignored(): void
    {
        ($this->update)(['hack' => 'x']);

        $this->assertSame([], $this->repository->stored);
    }

    public function test_missing_keys_leave_stored_values_untouched(): void
    {
        ($this->update)(['retention_days' => 90]);
        ($this->update)(['prune_schedule_enabled' => true]);

        $this->assertSame('90', $this->repository->stored['general']['retention_days']);
        $this->assertSame('1', $this->repository->stored['general']['prune_schedule_enabled']);
    }

    public function test_reset_removes_every_registered_setting(): void
    {
        ($this->update)(['retention_days' => 90, 'prune_schedule_enabled' => false]);

        (new ResetSettingsAction($this->repository, new SettingRegistry))();

        $this->assertSame([], array_filter($this->repository->stored));
    }
}
