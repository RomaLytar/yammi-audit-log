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
            $table->string('chain_head')->nullable();
            $table->unsignedBigInteger('record_count');
            $table->timestamp('range_start')->nullable();
            $table->timestamp('range_end')->nullable();
            $table->timestamp('generated_at');
            $table->string('algorithm', 32)->nullable();
            $table->text('signature')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('generated_at');
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

        return (is_string($table) ? $table : 'audit_log').'_digests';
    }
};
