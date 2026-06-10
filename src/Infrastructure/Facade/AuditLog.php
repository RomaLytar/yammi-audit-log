<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Facade;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Yammi\AuditLog\Application\DTO\TimelineData;
use Yammi\AuditLog\Infrastructure\Reader\AuditReader;

/**
 * @method static TimelineData for(Model|string $auditable, int|string|null $id = null, int $limit = 50)
 *
 * @see AuditReader
 */
final class AuditLog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuditReader::class;
    }
}
