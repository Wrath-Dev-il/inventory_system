<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (int) $this->user()->account_type === 1;
    }

    public function rules(): array
    {
        return [
            'billing_date' => ['nullable', 'date'],
            'transaction_type' => ['nullable', Rule::in(['vat_ex', 'vat_inc'])],
            'po_no' => ['nullable', 'string', 'max:100'],
            'sales_invoice_no' => ['nullable', 'string', 'max:100'],
            'quotation_no' => ['nullable', 'string', 'max:100'],
            'initial_payment_status' => ['required', Rule::in(['paid', 'unpaid'])],
            'final_payment_status' => ['required', Rule::in(['paid', 'unpaid'])],
            'actual_payment_remarks' => ['nullable', 'string', 'max:2000'],
            'sales_channel' => ['nullable', Rule::in(['Caloocan', 'Laguna'])],
        ];
    }

    public function messages(): array
    {
        return [
            'sales_channel.in' => 'Sales Channel must be Caloocan or Laguna.',
        ];
    }
}
