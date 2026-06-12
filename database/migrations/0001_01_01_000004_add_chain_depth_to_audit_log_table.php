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
            $table->unsignedTinyInteger('chain_depth')->default(0);
        });
    }

    public function down(): void
    {
        $this->schema()->table($this->table(), function (Blueprint $table): void {
            $table->dropColumn('chain_depth');
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
