<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->schema()->create('audit_log_chain_state', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('last_hash', 64)->nullable();
        });

        $head = $this->connection()->table($this->table())->orderByDesc('id')->value('integrity_hash');

        $this->connection()->table('audit_log_chain_state')->insert([
            'id' => 1,
            'last_hash' => is_string($head) ? $head : null,
        ]);
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('audit_log_chain_state');
    }

    private function schema(): Builder
    {
        return Schema::connection($this->connectionName());
    }

    private function connection(): ConnectionInterface
    {
        return DB::connection($this->connectionName());
    }

    private function connectionName(): ?string
    {
        $connection = config('audit-log.database.connection');

        return is_string($connection) ? $connection : null;
    }

    private function table(): string
    {
        $table = config('audit-log.database.table', 'audit_log');

        return is_string($table) ? $table : 'audit_log';
    }
};
