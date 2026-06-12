<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;

final class Profile extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'profiles';

    /** @var list<string> */
    public array $auditInclude = ['status', 'role'];
}
