<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Contracts\ShouldAudit;

final class Document extends Model implements ShouldAudit
{
    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'documents';

    /** @var list<string> */
    public array $auditExclude = ['internal_notes'];
}
