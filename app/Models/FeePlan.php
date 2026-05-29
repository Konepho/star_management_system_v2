<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'name',
        'code',
        'grade_group',
        'status',
        'description',
    ];

    public static function statusOptions(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function feeStructures(): BelongsToMany
    {
        return $this->belongsToMany(FeeStructure::class, 'fee_plan_fee_structure')
            ->withTimestamps()
            ->orderBy('fee_structures.fee_category_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function getGradeGroupLabelAttribute(): ?string
    {
        if (! $this->grade_group) {
            return null;
        }

        return Grade::groupOptions()[$this->grade_group] ?? ucfirst((string) $this->grade_group);
    }
}
