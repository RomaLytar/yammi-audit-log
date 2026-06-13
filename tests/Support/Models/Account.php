<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Yammi\AuditLog\Concerns\AuditsPivots;

final class Account extends Model
{
    use AuditsPivots;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'accounts';

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'account_role');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'owner_id');
    }
}
