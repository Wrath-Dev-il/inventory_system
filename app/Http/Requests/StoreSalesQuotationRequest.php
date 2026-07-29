<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->account_type === 1;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'sales' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'payment_terms' => ['nullable', 'string', 'max:2000'],
            'cancellation_terms' => ['nullable', 'string', 'max:2000'],
            'delivery_terms' => ['nullable', 'string', 'max:2000'],
            'lead_time_at' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'warranty' => ['nullable', 'string', 'max:2000'],
            'mode_of_payment' => ['nullable', 'string', 'max:255'],
            'attention_to' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.offer_description' => ['nullable', 'string', 'max:2000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',
            'items.required' => 'Please select at least one product.',
            'items.*.quantity.gt' => 'Quantity must be greater than zero for all items.',
            'items.*.unit_price.min' => 'Unit price must be zero or greater.',
            'valid_until.after_or_equal' => 'Quotation validity must be today or later.',
        ];
    }
}
