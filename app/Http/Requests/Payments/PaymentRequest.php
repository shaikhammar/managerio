<?php

namespace App\Http\Requests\Payments;

use App\Models\Payment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Payment::class);
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
            'amount' => 'required|numeric|min:0.01',
            'currency_code' => 'nullable|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'bank_account_id' => ['required', Rule::exists('accounts', 'id')->where('business_id', $businessId)],
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'allocations' => 'nullable|array',
            'allocations.*.invoice_id' => ['required_with:allocations', Rule::exists('invoices', 'id')->where('business_id', $businessId)],
            'allocations.*.amount' => 'required_with:allocations|numeric|min:0.01',
        ];
    }
}
