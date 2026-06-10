<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Tests\Support\Models\Note;
use Yammi\AuditLog\Tests\TestCase;

final class NoiseRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_the_dashboard_marks_a_noisy_write(): void
    {
        $note = Note::create(['title' => 'A', 'status' => 'draft']);
        $note->touch();

        $this->get('audit-log')->assertOk()->assertSee('no-op');
    }

    public function test_the_noise_page_lists_noisy_writes(): void
    {
        $note = Note::create(['title' => 'A', 'status' => 'draft']);
        $note->touch();
        $note->update(['status' => 'published']);

        $response = $this->get('audit-log/noise');

        $response->assertOk();
        $response->assertSee('Noisy writes');
        $response->assertSee('1 records');
    }

    public function test_the_noise_page_is_empty_without_noise(): void
    {
        Note::create(['title' => 'A', 'status' => 'draft']);

        $this->get('audit-log/noise')->assertOk()->assertSee('No noisy writes');
    }
}
