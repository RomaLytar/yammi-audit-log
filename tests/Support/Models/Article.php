<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Concerns\LogsAccess;

final class Article extends Model
{
    use LogsAccess;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'articles';
}
