<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    public const OFFICIAL_SLUGS = [
        'super_admin',
        'principal',
        'vice_principal',
        'section_head',
        'teacher',
        'registrar_cashier',
        'pos_cashier',
        'finance_manager',
        'hr_manager',
        'librarian',
        'operations_staff',
        'staff_self_service',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function scopeOfficial(Builder $query): Builder
    {
        return $query->whereIn('slug', self::OFFICIAL_SLUGS);
    }
}
