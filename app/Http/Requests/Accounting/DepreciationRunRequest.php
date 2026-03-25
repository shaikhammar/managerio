<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class DepreciationRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'date_format:Y-m'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.date_format' => 'The period must be in YYYY-MM format (e.g. 2026-03).',
        ];
    }
}
