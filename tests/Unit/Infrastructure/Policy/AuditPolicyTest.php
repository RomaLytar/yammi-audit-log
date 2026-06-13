<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Policy;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Policy\AuditPolicy;
use Yammi\AuditLog\Infrastructure\Policy\AuditPolicyRegistry;
use Yammi\AuditLog\Tests\Support\Models\Note;
use Yammi\AuditLog\Tests\Support\Models\Post;

final class AuditPolicyTest extends TestCase
{
    public function test_ignore_stores_the_fields(): void
    {
        $policy = (new AuditPolicy(Post::class))->ignore(['status', 'internal']);

        $this->assertSame(['status', 'internal'], $policy->ignoredFields());
    }

    public function test_allows_is_true_without_a_predicate(): void
    {
        $this->assertTrue((new AuditPolicy(Post::class))->allows(new Post));
    }

    public function test_sample_rate_defaults_to_null_and_clamps_to_0_1(): void
    {
        $this->assertNull((new AuditPolicy(Post::class))->sampleRate());
        $this->assertSame(0.0, (new AuditPolicy(Post::class))->sample(-1.0)->sampleRate());
        $this->assertSame(1.0, (new AuditPolicy(Post::class))->sample(5.0)->sampleRate());
        $this->assertSame(0.25, (new AuditPolicy(Post::class))->sample(0.25)->sampleRate());
    }

    public function test_allows_evaluates_the_predicate_against_the_model(): void
    {
        $post = new Post;
        $post->setRawAttributes(['status' => 'draft'], true);

        $policy = (new AuditPolicy(Post::class))->when(
            static fn (Post $model): bool => $model->getAttribute('status') === 'published',
        );

        $this->assertFalse($policy->allows($post));

        $post->setRawAttributes(['status' => 'published'], true);
        $this->assertTrue($policy->allows($post));
    }

    public function test_registry_memoizes_one_policy_per_model(): void
    {
        $registry = new AuditPolicyRegistry;

        $this->assertSame($registry->policy(Post::class), $registry->policy(Post::class));
    }

    public function test_registry_matches_by_class_and_instance(): void
    {
        $registry = new AuditPolicyRegistry;
        $registry->policy(Post::class)->ignore(['status']);

        $this->assertNotNull($registry->for(new Post));
        $this->assertNull($registry->for(new Note));
    }
}
