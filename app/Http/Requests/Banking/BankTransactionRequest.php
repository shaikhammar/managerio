<?php

namespace App\Http\Requests\Banking;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BankTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bank_account_id' => 'required|exists:accounts,id',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|not_in:0',
            'reference' => 'nullable|string|max:255',
        ];
    }
}
