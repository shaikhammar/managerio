<?php

namespace App\Http\Requests\Accounting;

use App\Domain\Accounting\Enums\AccountType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AccountRequest extends FormRequest
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
        $types = implode(',', array_column(AccountType::cases(), 'value'));

        return [
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'type' => "required|string|in:{$types}",
            'sub_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:accounts,id',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
