<?php

namespace App\Http\Requests\Translation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TranslationMemoryRequest extends FormRequest
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

        return [
            'name' => ['required', 'string', 'max:255'],
            'source_language_id' => ['required', 'exists:languages,id'],
            'target_language_id' => ['required', 'exists:languages,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'software' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
