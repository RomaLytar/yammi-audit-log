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
            $table->string('auditable_type', 191)->nullable();
            $table->string('event', 16)->nullable();
            $table->string('exception', 191)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
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

        return (is_string($table) ? $table : 'audit_log').'_capture_failures';
    }
};
