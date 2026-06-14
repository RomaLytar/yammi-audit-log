<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;

final class Role extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'roles';
}
