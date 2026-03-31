<?php

namespace App\Http\Requests\Translation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StyleGuideRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'description' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,txt'],
        ];
    }
}
