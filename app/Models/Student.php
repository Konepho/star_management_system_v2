<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Student extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_GRADUATED = 'graduated';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'user_id',
        'admission_no',
        'name_mm',
        'name_en',
        'preferred_name',
        'first_name',
        'last_name',
        'gender',
        'student_type',
        'previous_school_name',
        'date_of_birth',
        'admission_date',
        'email',
        'contact_number',
        'emergency_contact_number',
        'phone',
        'address',
        'photo_path',
        'card_color',
        'status',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'admission_date' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_GRADUATED => 'Graduated',
            self::STATUS_TRANSFERRED => 'Transferred',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class)->orderByDesc('enrollment_date')->orderByDesc('id');
    }

    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->orderByDesc('enrollment_date')
            ->orderByDesc('id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(StudentInvoice::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class)->orderBy('relation')->orderBy('id');
    }

    public function healthProfile(): HasOne
    {
        return $this->hasOne(StudentHealthProfile::class);
    }

    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(StudentDiscount::class);
    }

    public function externalExamRegistrations(): HasMany
    {
        return $this->hasMany(ExternalExamRegistration::class)->orderByDesc('registration_date')->orderByDesc('id');
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(StudentDailyReport::class)->orderByDesc('report_date')->orderByDesc('id');
    }

    public function fatherGuardian(): HasOne
    {
        return $this->hasOne(Guardian::class)->where('relation', Guardian::RELATION_FATHER);
    }

    public function motherGuardian(): HasOne
    {
        return $this->hasOne(Guardian::class)->where('relation', Guardian::RELATION_MOTHER);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $legacyName = trim($this->first_name . ' ' . $this->last_name);

                if ($legacyName !== '' && $this->last_name !== '') {
                    return $legacyName;
                }

                return $this->name_en ?: $legacyName;
            },
        );
    }

    protected function academicYearId(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? $this->currentEnrollment()?->academic_year_id,
        );
    }

    protected function gradeId(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? $this->currentEnrollment()?->grade_id,
        );
    }

    protected function sectionId(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? $this->currentEnrollment()?->section_id,
        );
    }

    public function currentEnrollment(): ?Enrollment
    {
        if ($this->relationLoaded('activeEnrollments')) {
            return $this->activeEnrollments->first();
        }

        if ($this->relationLoaded('enrollments')) {
            return $this->enrollments->firstWhere('status', Enrollment::STATUS_ACTIVE);
        }

        return $this->activeEnrollments()
            ->with(['academicYear', 'grade', 'section', 'feePlan'])
            ->first();
    }

    protected function mappedEnrollmentStatus(): string
    {
        return match ($this->status) {
            self::STATUS_GRADUATED => Enrollment::STATUS_COMPLETED,
            self::STATUS_TRANSFERRED => Enrollment::STATUS_TRANSFERRED,
            self::STATUS_INACTIVE, self::STATUS_ARCHIVED => Enrollment::STATUS_INACTIVE,
            default => Enrollment::STATUS_ACTIVE,
        };
    }
}
