<?php

namespace App\Http\Requests\Accounting;

use App\Domain\Accounting\Enums\DepreciationMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FixedAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'asset_tag' => ['nullable', 'string', 'max:100'],
            'asset_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'accumulated_depreciation_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'depreciation_expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'purchase_date' => ['required', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0.01'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_months' => ['required', 'integer', 'min:1', 'max:600'],
            'depreciation_method' => ['required', Rule::enum(DepreciationMethod::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'purchase_cost.min' => 'The purchase cost must be greater than zero.',
            'useful_life_months.min' => 'The useful life must be at least 1 month.',
            'useful_life_months.max' => 'The useful life cannot exceed 600 months (50 years).',
        ];
    }
}
