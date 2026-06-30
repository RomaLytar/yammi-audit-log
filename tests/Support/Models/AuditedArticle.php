<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Concerns\HasAuditTrail;

final class AuditedArticle extends Model
{
    use HasAuditTrail;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'articles';
}
