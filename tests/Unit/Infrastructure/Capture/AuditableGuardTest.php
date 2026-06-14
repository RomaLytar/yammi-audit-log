<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Capture;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Capture\AuditableGuard;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditChainStateModel;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Models\Document;
use Yammi\AuditLog\Tests\Support\Models\Post;

final class AuditableGuardTest extends TestCase
{
    public function test_a_regular_model_with_a_key_is_audited(): void
    {
        $this->assertTrue((new AuditableGuard([]))->shouldAudit($this->post(5)));
    }

    public function test_the_audit_record_model_itself_is_never_audited(): void
    {
        $model = new AuditRecordModel;
        $model->setRawAttributes(['id' => 1], true);

        $this->assertFalse((new AuditableGuard([]))->shouldAudit($model));
    }

    public function test_the_chain_state_model_is_never_audited(): void
    {
        $model = new AuditChainStateModel;
        $model->setRawAttributes(['id' => 1], true);

        $this->assertFalse((new AuditableGuard([]))->shouldAudit($model));
    }

    public function test_a_model_without_a_key_is_skipped(): void
    {
        $this->assertFalse((new AuditableGuard([]))->shouldAudit(new Post));
    }

    public function test_an_excluded_class_is_skipped(): void
    {
        $guard = new AuditableGuard([Post::class]);

        $this->assertFalse($guard->shouldAudit($this->post(5)));
    }

    public function test_exclusion_covers_subclasses(): void
    {
        $guard = new AuditableGuard([Model::class]);

        $this->assertFalse($guard->shouldAudit($this->post(5)));
    }

    public function test_opt_in_mode_only_audits_marked_models(): void
    {
        $guard = new AuditableGuard([], AuditableGuard::MODE_OPT_IN);

        $document = new Document;
        $document->setRawAttributes(['id' => 1], true);

        $this->assertTrue($guard->shouldAudit($document));
        $this->assertFalse($guard->shouldAudit($this->post(5)));
    }

    private function post(int $id): Post
    {
        $post = new Post;
        $post->setRawAttributes(['id' => $id], true);

        return $post;
    }
}
