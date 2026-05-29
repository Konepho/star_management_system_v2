<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    public const TYPE_CLASSROOM = 'classroom';
    public const TYPE_OFFICE = 'office';
    public const TYPE_LAB = 'lab';
    public const TYPE_LIBRARY = 'library';
    public const TYPE_CLINIC = 'clinic';
    public const TYPE_HALL = 'hall';
    public const TYPE_OTHER = 'other';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_MAINTENANCE = 'maintenance';

    protected $fillable = [
        'name',
        'code',
        'building',
        'floor',
        'capacity',
        'room_type',
        'status',
    ];

    public static function typeOptions(): array
    {
        return [
            self::TYPE_CLASSROOM => 'Classroom',
            self::TYPE_OFFICE => 'Office',
            self::TYPE_LAB => 'Lab',
            self::TYPE_LIBRARY => 'Library',
            self::TYPE_CLINIC => 'Clinic',
            self::TYPE_HALL => 'Hall',
            self::TYPE_OTHER => 'Other',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_MAINTENANCE => 'Maintenance',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
