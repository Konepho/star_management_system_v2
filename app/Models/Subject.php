<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_core',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
        ];
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }
}
