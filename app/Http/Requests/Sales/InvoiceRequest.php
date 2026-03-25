<?php

namespace App\Http\Requests\Sales;

use App\Models\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        if ($invoice instanceof Invoice) {
            return $this->user()->can('update', $invoice);
        }

        return $this->user()->can('create', Invoice::class);
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
            'due_date' => 'nullable|date|after_or_equal:date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
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
