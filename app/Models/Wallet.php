<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Wallet extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'current_balance',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'current_balance' => 'decimal:2',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function ownerName(): string
    {
        return match ($this->owner_type) {
            Student::class => (string) ($this->owner?->full_name ?? 'Student'),
            Staff::class => (string) ($this->owner?->full_name ?? 'Staff'),
            default => 'Wallet Owner',
        };
    }

    public function ownerIdentifier(): string
    {
        return match ($this->owner_type) {
            Student::class => (string) ($this->owner?->admission_no ?? ''),
            Staff::class => (string) ($this->owner?->staff_no ?? ''),
            default => '',
        };
    }

    public function ownerTypeLabel(): string
    {
        return $this->owner_type === Staff::class ? 'Staff' : 'Student';
    }
}
