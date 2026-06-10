<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Capture;

use DateTimeImmutable;
use Illuminate\Support\Stringable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Infrastructure\Capture\ChangeDataFactory;
use Yammi\AuditLog\Tests\Support\Models\Post;

final class ChangeDataFactoryTest extends TestCase
{
    private ChangeDataFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ChangeDataFactory;
    }

    public function test_a_creation_has_no_before_state(): void
    {
        $post = new Post;
        $post->setRawAttributes(['id' => 1, 'status' => 'draft'], true);

        $change = $this->factory->make($post, ChangeType::Created);

        $this->assertSame([], $change->before);
        $this->assertSame(['id' => 1, 'status' => 'draft'], $change->after);
        $this->assertSame('1', $change->auditableId);
        $this->assertSame($post->getMorphClass(), $change->auditableType);
    }

    public function test_a_deletion_has_no_after_state(): void
    {
        $post = new Post;
        $post->setRawAttributes(['id' => 1, 'status' => 'draft'], true);

        $change = $this->factory->make($post, ChangeType::Deleted);

        $this->assertSame(['id' => 1, 'status' => 'draft'], $change->before);
        $this->assertSame([], $change->after);
    }

    public function test_an_update_keeps_only_the_changed_attributes(): void
    {
        $post = new Post;
        $post->setRawAttributes(['id' => 1, 'status' => 'draft', 'title' => 'A'], true);
        $post->status = 'published';
        $post->syncChanges();

        $change = $this->factory->make($post, ChangeType::Updated);

        $this->assertSame(['status' => 'draft'], $change->before);
        $this->assertSame(['status' => 'published'], $change->after);
    }

    public function test_object_values_are_normalized_to_scalars(): void
    {
        $post = new Post;
        $post->setRawAttributes([
            'id' => 1,
            'kind' => ChangeType::Created,
            'seen_at' => new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
            'slug' => new Stringable('hello'),
            'meta' => (object) ['a' => 1],
        ], true);

        $change = $this->factory->make($post, ChangeType::Created);

        $this->assertSame('created', $change->after['kind']);
        $this->assertSame('2026-01-01T10:00:00+00:00', $change->after['seen_at']);
        $this->assertSame('hello', $change->after['slug']);
        $this->assertSame('{"a":1}', $change->after['meta']);
    }

    public function test_scalar_and_array_values_pass_through(): void
    {
        $post = new Post;
        $post->setRawAttributes([
            'id' => 1,
            'count' => 5,
            'price' => 9.5,
            'active' => true,
            'tags' => ['a', 'b'],
            'note' => null,
        ], true);

        $change = $this->factory->make($post, ChangeType::Created);

        $this->assertSame(5, $change->after['count']);
        $this->assertSame(9.5, $change->after['price']);
        $this->assertTrue($change->after['active']);
        $this->assertSame(['a', 'b'], $change->after['tags']);
        $this->assertNull($change->after['note']);
    }
}
