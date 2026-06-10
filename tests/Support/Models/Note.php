<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Note extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $table = 'notes';
}
