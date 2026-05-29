<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalExamSession extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'academic_year_id',
        'name',
        'exam_body',
        'level',
        'exam_date',
        'registration_deadline',
        'fee_amount',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'registration_deadline' => 'date',
            'fee_amount' => 'decimal:2',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ExternalExamRegistration::class)->orderByDesc('registration_date')->orderByDesc('id');
    }
}
