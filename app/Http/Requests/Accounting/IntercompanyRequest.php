<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IntercompanyRequest extends FormRequest
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
        $businessId = $this->user()->currentBusiness()->id;

        return [
            'source_account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where('business_id', $businessId),
            ],
            'target_business_id' => [
                'required',
                'integer',
                Rule::notIn([$businessId]),
                Rule::exists('businesses', 'id'),
            ],
            'target_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_business_id.not_in' => 'The target business must be different from the source business.',
        ];
    }
}
