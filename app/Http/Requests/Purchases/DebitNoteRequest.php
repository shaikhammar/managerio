<?php

namespace App\Http\Requests\Purchases;

use App\Models\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DebitNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Invoice::class);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contact_id.required' => 'Please select a supplier.',
            'contact_id.exists' => 'The selected supplier is invalid.',
            'date.required' => 'Please enter a debit note date.',
            'lines.required' => 'At least one line item is required.',
            'lines.min' => 'At least one line item is required.',
            'lines.*.account_id.required' => 'Please select an account for each line item.',
            'lines.*.account_id.exists' => 'The selected account on a line item is invalid.',
            'lines.*.description.required' => 'Please enter a description for each line item.',
            'lines.*.quantity.required' => 'Quantity is required for each line item.',
            'lines.*.quantity.numeric' => 'Quantity must be a number.',
            'lines.*.quantity.min' => 'Quantity must be greater than zero.',
            'lines.*.unit_price.required' => 'Unit price is required for each line item.',
            'lines.*.unit_price.numeric' => 'Unit price must be a number.',
            'lines.*.unit_price.min' => 'Unit price cannot be negative.',
            'lines.*.discount_percent.numeric' => 'Discount must be a number.',
            'lines.*.discount_percent.between' => 'Discount must be between 0 and 100.',
            'lines.*.tax_code_id.exists' => 'The selected tax code on a line item is invalid.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $businessId = session('current_business_id');

        return [
            'contact_id' => ['required', Rule::exists('contacts', 'id')->where('business_id', $businessId)],
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'currency_code' => 'nullable|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => ['required', Rule::exists('accounts', 'id')->where('business_id', $businessId)],
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|between:0,100',
            'lines.*.tax_code_id' => ['nullable', Rule::exists('tax_codes', 'id')->where('business_id', $businessId)],
        ];
    }
}
