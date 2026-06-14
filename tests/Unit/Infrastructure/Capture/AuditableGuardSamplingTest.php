<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Capture;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Capture\AuditableGuard;
use Yammi\AuditLog\Infrastructure\Policy\AuditPolicyRegistry;
use Yammi\AuditLog\Tests\Support\FixedCorrelationResolver;
use Yammi\AuditLog\Tests\Support\Models\Post;

final class AuditableGuardSamplingTest extends TestCase
{
    public function test_rate_zero_drops_every_change(): void
    {
        $this->assertFalse($this->guard(0.0, 'corr')->shouldAudit($this->post()));
    }

    public function test_rate_one_keeps_every_change(): void
    {
        $this->assertTrue($this->guard(1.0, 'corr')->shouldAudit($this->post()));
    }

    public function test_a_partial_rate_keeps_the_change_when_there_is_no_correlation(): void
    {
        $this->assertTrue($this->guard(0.5, null)->shouldAudit($this->post()));
    }

    public function test_the_decision_is_stable_per_correlation_and_actually_samples(): void
    {
        $kept = 0;
        $total = 200;

        for ($i = 0; $i < $total; $i++) {
            $guard = $this->guard(0.5, 'corr-'.$i);

            $decision = $guard->shouldAudit($this->post());
            $this->assertSame($decision, $guard->shouldAudit($this->post()));

            $kept += $decision ? 1 : 0;
        }

        $this->assertGreaterThan(0, $kept);
        $this->assertLessThan($total, $kept);
    }

    private function guard(float $rate, ?string $correlation): AuditableGuard
    {
        $registry = new AuditPolicyRegistry;
        $registry->policy(Post::class)->sample($rate);

        return new AuditableGuard([], AuditableGuard::MODE_ALL, $registry, new FixedCorrelationResolver($correlation));
    }

    private function post(): Post
    {
        $post = new Post;
        $post->setRawAttributes(['id' => 1], true);

        return $post;
    }
}
