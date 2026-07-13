<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtraClassOffering extends Model
{
    use HasFactory;

    protected $fillable = [
        'extra_class_catalog_id',
        'academic_year_id',
        'fee_category_id',
        'name',
        'fee_amount',
        'start_date',
        'end_date',
        'remarks',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'fee_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(ExtraClassCatalog::class, 'extra_class_catalog_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(StudentExtraClassRegistration::class)->orderByDesc('registration_date')->orderByDesc('id');
    }

    public function getDisplayNameAttribute(): string
    {
        return trim((string) ($this->name ?: $this->catalog?->name ?: 'Extra Class'));
    }
}
