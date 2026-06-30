<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\ValueObject;

use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;

/**
 * One unit of work in a causation tree: an HTTP request, a command, a scheduled
 * task or a queued job. Its id is shared by every change the unit makes; its
 * parentId points to the span that caused it (the request or job that dispatched
 * it), so spans of one correlation form a tree rather than a flat list.
 */
final class Span
{
    public readonly string $id;

    public readonly ?string $parentId;

    public function __construct(string $id, ?string $parentId = null)
    {
        $id = trim($id);

        if ($id === '') {
            throw InvalidAuditData::emptyValue('span id');
        }

        $parentId = $parentId === null ? null : trim($parentId);

        $this->id = $id;
        $this->parentId = $parentId === '' ? null : $parentId;
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id && $this->parentId === $other->parentId;
    }
}
