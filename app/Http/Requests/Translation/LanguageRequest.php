<?php

namespace App\Http\Requests\Translation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanguageRequest extends FormRequest
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
        $businessId = session('current_business_id');
        $languageId = $this->route('language')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('languages', 'code')
                    ->where('business_id', $businessId)
                    ->ignore($languageId),
            ],
            'name' => 'required|string|max:100',
            'native_name' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
