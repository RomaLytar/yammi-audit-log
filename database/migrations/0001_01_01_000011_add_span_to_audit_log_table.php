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
        $this->schema()->table($this->table(), function (Blueprint $table): void {
            $table->uuid('span_id')->nullable()->index();
            $table->uuid('parent_span_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        $this->schema()->table($this->table(), function (Blueprint $table): void {
            $table->dropColumn(['span_id', 'parent_span_id']);
        });
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
