<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Label;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\ChangeData;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Infrastructure\Label\ConventionLabelResolver;

final class ConventionLabelResolverTest extends TestCase
{
    public function test_an_empty_map_resolves_no_labels(): void
    {
        $resolver = new ConventionLabelResolver([]);

        $this->assertTrue($resolver->labelsFor($this->change(['user_id' => 5]))->isEmpty());
    }

    public function test_a_non_model_mapping_is_ignored(): void
    {
        $resolver = new ConventionLabelResolver(['user_id' => 'NotAModelClass']);

        $this->assertTrue($resolver->labelsFor($this->change(['user_id' => 5]))->isEmpty());
    }

    public function test_unmapped_fields_are_ignored(): void
    {
        $resolver = new ConventionLabelResolver(['other_id' => 'NotAModelClass']);

        $this->assertTrue($resolver->labelsFor($this->change(['user_id' => 5]))->isEmpty());
    }

    /**
     * @param  array<string, scalar|null>  $after
     */
    private function change(array $after): ChangeData
    {
        return new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: ChangeType::Updated,
            before: [],
            after: $after,
        );
    }
}
