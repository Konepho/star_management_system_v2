<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtraClassCatalog extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'grade_group',
        'description',
        'status',
    ];

    public static function statusOptions(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(ExtraClassOffering::class)->orderByDesc('academic_year_id')->orderBy('name');
    }
}
