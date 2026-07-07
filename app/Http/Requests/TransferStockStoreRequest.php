<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferStockStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_store_id' => ['required', 'exists:stores,id', 'different:to_store_id'],
            'to_store_id' => ['required', 'exists:stores,id'],
            'date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_store_id.different' => 'Toko asal dan tujuan tidak boleh sama.',
            'items.required' => 'Minimal 1 item produk harus ditambahkan.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.quantity.min' => 'Kuantitas minimal 1.',
        ];
    }
}
