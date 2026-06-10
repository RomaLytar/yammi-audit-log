<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Yammi\AuditLog\Tests\Support\Models\Post;

final class InnerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly int $postId,
    ) {}

    public function handle(): void
    {
        Post::query()->findOrFail($this->postId)->update(['status' => 'inner']);
    }
}
