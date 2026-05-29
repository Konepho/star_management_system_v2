<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'staff_no',
        'first_name',
        'last_name',
        'gender',
        'phone',
        'email',
        'department',
        'designation',
        'join_date',
        'address',
        'photo_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sectionAssignments(): HasMany
    {
        return $this->hasMany(StaffSectionAssignment::class);
    }

    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    public function assignedSectionIds(?int $academicYearId = null): Collection
    {
        if (! $this->exists) {
            return collect();
        }

        $assignments = $this->relationLoaded('sectionAssignments')
            ? $this->sectionAssignments
            : $this->sectionAssignments()->get();

        return $assignments
            ->when($academicYearId, fn (Collection $items) => $items->where('academic_year_id', $academicYearId))
            ->pluck('section_id')
            ->map(fn (mixed $sectionId) => (int) $sectionId)
            ->unique()
            ->values();
    }

    public function latestAssignedAcademicYearId(): ?int
    {
        if (! $this->exists) {
            return null;
        }

        $assignments = $this->relationLoaded('sectionAssignments')
            ? $this->sectionAssignments
            : $this->sectionAssignments()->orderByDesc('academic_year_id')->get();

        return $assignments
            ->sortByDesc('academic_year_id')
            ->pluck('academic_year_id')
            ->map(fn (mixed $academicYearId) => (int) $academicYearId)
            ->first();
    }

    public function displayName(): string
    {
        $name = trim(collect([$this->first_name, $this->last_name])->filter()->implode(' '));

        if ($this->isLikelyPhoneNumber($name)) {
            $userName = trim((string) $this->user?->name);

            if ($userName !== '') {
                return $userName;
            }
        }

        return $name;
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->displayName(),
        );
    }

    private function isLikelyPhoneNumber(?string $value): bool
    {
        $value = trim((string) $value);

        if ($value === '') {
            return false;
        }

        $digitsOnly = preg_replace('/\D+/', '', $value);

        return strlen($digitsOnly) >= 7
            && strlen($digitsOnly) >= max(strlen($value) - 2, 0);
    }
}
