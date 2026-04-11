<?php

namespace App\Http\Requests\Sales;

use App\Models\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quote = $this->route('quote');

        if ($quote instanceof Invoice) {
            return $this->user()->can('update', $quote);
        }

        return $this->user()->can('create', Invoice::class);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contact_id.required' => 'Please select a customer.',
            'contact_id.exists' => 'The selected customer is invalid.',
            'date.required' => 'Please enter a quote date.',
            'due_date.after_or_equal' => 'The expiry date cannot be before the quote date.',
            'lines.required' => 'At least one line item is required.',
            'lines.min' => 'At least one line item is required.',
            'lines.*.description.required' => 'Please enter a description for each line item.',
            'lines.*.quantity.required' => 'Quantity is required for each line item.',
            'lines.*.quantity.numeric' => 'Quantity must be a number.',
            'lines.*.quantity.min' => 'Quantity must be greater than zero.',
            'lines.*.unit_price.required' => 'Unit price is required for each line item.',
            'lines.*.unit_price.numeric' => 'Unit price must be a number.',
            'lines.*.unit_price.min' => 'Unit price cannot be negative.',
            'lines.*.discount_percent.numeric' => 'Discount must be a number.',
            'lines.*.discount_percent.between' => 'Discount must be between 0 and 100.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $businessId = session('current_business_id');

        return [
            'contact_id' => ['required', Rule::exists('contacts', 'id')->where('business_id', $businessId)],
            'date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:date',
            'reference' => 'nullable|string|max:255',
            'currency_code' => 'nullable|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => ['nullable', Rule::exists('accounts', 'id')->where('business_id', $businessId)],
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|between:0,100',
            'lines.*.tax_code_id' => 'nullable|string', // Support "none" sentinel
        ];
    }
}
