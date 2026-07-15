<?php

namespace App\Http\Requests;

use App\Models\PosSale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pos_section' => ['required', Rule::in([PosSale::SECTION_SNACK_SHOP, PosSale::SECTION_STATIONERY])],
            'wallet_id' => ['required', 'exists:wallets,id'],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'exists:pos_products,id'],
            'quantities' => ['required', 'array', 'min:1'],
            'quantities.*' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('pos_section')) {
            $this->merge(['pos_section' => PosSale::SECTION_SNACK_SHOP]);
        }
    }
}
