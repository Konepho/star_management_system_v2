<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosProductCategory extends Model
{
    use HasFactory;

    public const SECTION_SNACK_SHOP = 'snack_shop';

    public const SECTION_STATIONERY = 'stationery';

    protected $fillable = [
        'name',
        'code',
        'description',
        'pos_section',
        'status',
    ];

    public static function sectionOptions(): array
    {
        return [
            self::SECTION_SNACK_SHOP => 'Snack Shop',
            self::SECTION_STATIONERY => 'Stationery',
        ];
    }

    public function sectionLabel(): string
    {
        return self::sectionOptions()[$this->pos_section] ?? 'Snack Shop';
    }

    public function products(): HasMany
    {
        return $this->hasMany(PosProduct::class);
    }
}
