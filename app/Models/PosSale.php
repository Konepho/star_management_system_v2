<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PosSale extends Model
{
    use HasFactory;

    public const STATUS_POSTED = 'posted';
    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'sale_no',
        'wallet_id',
        'owner_type',
        'owner_id',
        'total_amount',
        'balance_before',
        'balance_after',
        'status',
        'notes',
        'performed_by_user_id',
        'reversed_at',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'reversed_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class)->orderBy('id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    public function ownerName(): string
    {
        return $this->owner_type === Staff::class
            ? (string) ($this->owner?->full_name ?? 'Staff')
            : (string) ($this->owner?->full_name ?? 'Student');
    }

    public function ownerIdentifier(): string
    {
        return $this->owner_type === Staff::class
            ? (string) ($this->owner?->staff_no ?? '')
            : (string) ($this->owner?->admission_no ?? '');
    }

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED || $this->reversed_at !== null;
    }
}
