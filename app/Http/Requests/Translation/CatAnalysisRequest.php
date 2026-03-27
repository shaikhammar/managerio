<?php

namespace App\Http\Requests\Translation;

use App\Domain\Translation\Enums\CatMatchBand;
use App\Domain\Translation\Enums\CatTool;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatAnalysisRequest extends FormRequest
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
            'project_target_id' => ['required', 'integer', 'exists:project_targets,id'],
            'name' => ['required', 'string', 'max:100'],
            'tool' => ['required', Rule::enum(CatTool::class)],
            'bands' => ['required', 'array'],
            'bands.*.band' => ['required', Rule::enum(CatMatchBand::class)],
            'bands.*.words' => ['required', 'integer', 'min:0'],
            'bands.*.discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'bands.*.words.min' => 'Word counts must be zero or greater.',
            'bands.*.discount_percent.min' => 'Discount must be between 0 and 100.',
            'bands.*.discount_percent.max' => 'Discount must be between 0 and 100.',
        ];
    }
}
