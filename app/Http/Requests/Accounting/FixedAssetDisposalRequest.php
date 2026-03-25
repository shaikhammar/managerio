<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class FixedAssetDisposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disposal_date' => ['required', 'date'],
            'disposal_proceeds' => ['required', 'numeric', 'min:0'],
            'bank_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'gain_loss_account_id' => ['required', 'integer', 'exists:accounts,id'],
        ];
    }
}
