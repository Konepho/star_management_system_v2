<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guardian extends Model
{
    use HasFactory;

    public const RELATION_FATHER = 'father';
    public const RELATION_MOTHER = 'mother';
    public const RELATION_GUARDIAN = 'guardian';

    protected $fillable = [
        'student_id',
        'relation',
        'name',
        'occupation',
        'phone',
        'email',
        'address',
        'is_primary_contact',
        'is_emergency_contact',
    ];

    protected function casts(): array
    {
        return [
            'is_primary_contact' => 'boolean',
            'is_emergency_contact' => 'boolean',
        ];
    }

    public static function relationOptions(): array
    {
        return [
            self::RELATION_FATHER,
            self::RELATION_MOTHER,
            self::RELATION_GUARDIAN,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
