<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentHealthProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'blood_type',
        'allergies',
        'medical_conditions',
        'medications',
        'doctor_name',
        'doctor_phone',
        'emergency_medical_note',
        'health_remark',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
