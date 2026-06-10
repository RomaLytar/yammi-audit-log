<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->schema()->create($this->table(), function (Blueprint $table): void {
            $table->id();
            $table->string('auditable_type');
            $table->string('auditable_id', 64);
            $table->string('event', 16);
            $table->json('changes');
            $table->string('actor_type', 32);
            $table->string('actor_id')->nullable();
            $table->string('actor_label')->nullable();
            $table->string('origin_type', 32)->nullable();
            $table->string('origin_id')->nullable();
            $table->string('origin_label')->nullable();
            $table->json('labels');
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['actor_type', 'actor_id']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists($this->table());
    }

    private function schema(): Builder
    {
        $connection = config('audit-log.database.connection');

        return Schema::connection(is_string($connection) ? $connection : null);
    }

    private function table(): string
    {
        $table = config('audit-log.database.table', 'audit_log');

        return is_string($table) ? $table : 'audit_log';
    }
};
