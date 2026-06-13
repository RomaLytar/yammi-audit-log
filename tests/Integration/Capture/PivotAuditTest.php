<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Capture;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Models\Account;
use Yammi\AuditLog\Tests\Support\Models\Role;
use Yammi\AuditLog\Tests\Support\Models\User;
use Yammi\AuditLog\Tests\TestCase;

final class PivotAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('owner_id')->nullable();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create('account_role', function (Blueprint $table): void {
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('role_id');
        });
    }

    public function test_attach_records_an_attached_event_with_the_relation_diff(): void
    {
        $account = Account::create(['name' => 'Acme']);
        $role = Role::create(['name' => 'admin']);
        AuditRecordModel::query()->delete();

        $account->auditAttach('roles', $role->id);

        $record = AuditRecordModel::query()->where('event', 'attached')->firstOrFail();

        $this->assertSame($account->getMorphClass(), $record->auditable_type);
        $this->assertSame((string) $account->id, $record->auditable_id);
        $this->assertSame(['roles' => ['old' => [], 'new' => ['1']]], $record->changes);
    }

    public function test_attach_actually_mutates_the_relation(): void
    {
        $account = Account::create(['name' => 'Acme']);
        $role = Role::create(['name' => 'admin']);

        $account->auditAttach('roles', $role->id);

        $this->assertTrue($account->roles()->whereKey($role->id)->exists());
    }

    public function test_detach_records_a_detached_event_and_returns_the_count(): void
    {
        $account = Account::create(['name' => 'Acme']);
        $admin = Role::create(['name' => 'admin']);
        $editor = Role::create(['name' => 'editor']);
        $account->roles()->attach([$admin->id, $editor->id]);
        AuditRecordModel::query()->delete();

        $detached = $account->auditDetach('roles', $admin->id);

        $this->assertSame(1, $detached);

        $record = AuditRecordModel::query()->where('event', 'detached')->firstOrFail();
        $this->assertSame(['roles' => ['old' => ['1', '2'], 'new' => ['2']]], $record->changes);
    }

    public function test_sync_records_a_synced_event_with_before_and_after_sets(): void
    {
        $account = Account::create(['name' => 'Acme']);
        $admin = Role::create(['name' => 'admin']);
        $editor = Role::create(['name' => 'editor']);
        $viewer = Role::create(['name' => 'viewer']);
        $account->roles()->attach([$admin->id, $editor->id]);
        AuditRecordModel::query()->delete();

        $result = $account->auditSync('roles', [$editor->id, $viewer->id]);

        $this->assertEqualsCanonicalizing([$viewer->id], $result['attached']);
        $this->assertEqualsCanonicalizing([$admin->id], $result['detached']);

        $record = AuditRecordModel::query()->where('event', 'synced')->firstOrFail();
        $this->assertSame(['roles' => ['old' => ['1', '2'], 'new' => ['2', '3']]], $record->changes);
    }

    public function test_a_sync_that_changes_nothing_records_nothing(): void
    {
        $account = Account::create(['name' => 'Acme']);
        $role = Role::create(['name' => 'admin']);
        $account->roles()->attach($role->id);
        AuditRecordModel::query()->delete();

        $account->auditSync('roles', [$role->id]);

        $this->assertSame(0, AuditRecordModel::query()->count());
    }

    public function test_the_actor_is_attributed_like_any_captured_change(): void
    {
        $this->actingAs(new User(['id' => 7, 'name' => 'Jane Doe']));

        $account = Account::create(['name' => 'Acme']);
        $role = Role::create(['name' => 'admin']);
        AuditRecordModel::query()->delete();

        $account->auditAttach('roles', $role->id);

        $record = AuditRecordModel::query()->where('event', 'attached')->firstOrFail();
        $this->assertSame(ActorType::User->value, $record->actor_type);
        $this->assertSame('Jane Doe', $record->actor_label);
    }

    public function test_a_relation_that_is_not_many_to_many_is_rejected(): void
    {
        $account = Account::create(['name' => 'Acme']);

        $this->expectException(InvalidAuditData::class);

        $account->auditAttach('children', 1);
    }
}
