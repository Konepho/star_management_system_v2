<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentExtraClassRegistration extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'extra_class_offering_id',
        'registration_date',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(ExtraClassOffering::class, 'extra_class_offering_id');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(StudentInvoiceItem::class)->orderBy('id');
    }
}
