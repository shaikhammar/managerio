<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BudgetRequest extends FormRequest
{
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
            'year' => 'required|integer|min:2000|max:2100',
            'entries' => 'required|array|min:1',
            'entries.*.account_id' => 'required|exists:accounts,id',
            'entries.*.month' => 'required|integer|min:1|max:12',
            'entries.*.amount' => 'required|numeric|min:0',
        ];
    }
}
