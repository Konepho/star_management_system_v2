<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    use HasFactory;

    public const GROUP_PRESCHOOL = 'preschool';
    public const GROUP_PRIMARY = 'primary';
    public const GROUP_SECONDARY = 'secondary';
    public const GROUP_PEARSON_IGCSE = 'pearson_igcse';

    protected $fillable = [
        'name',
        'code',
        'grade_group',
        'sort_order',
        'remarks',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }

    public static function groupOptions(): array
    {
        return config('school.grade_groups', [
            self::GROUP_PRESCHOOL => 'Preschool',
            self::GROUP_PRIMARY => 'Primary',
            self::GROUP_SECONDARY => 'Secondary',
            self::GROUP_PEARSON_IGCSE => 'Pearson IGCSE',
        ]);
    }

    public function getGradeGroupLabelAttribute(): string
    {
        return self::groupOptions()[$this->grade_group] ?? ucfirst((string) $this->grade_group);
    }
}
