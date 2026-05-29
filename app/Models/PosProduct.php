<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_product_category_id',
        'name',
        'sku',
        'description',
        'price',
        'stock_quantity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(PosSaleItem::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PosProductCategory::class, 'pos_product_category_id');
    }
}
