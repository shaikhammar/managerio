<?php

namespace App\Http\Requests\Translation;

use App\Domain\Translation\Enums\CatTool;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatAnalysisImportRequest extends FormRequest
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
            'tool' => ['required', Rule::in([CatTool::Trados->value, CatTool::MemoQ->value, CatTool::Phrase->value])],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Only CSV files are supported for import.',
            'tool.in' => 'Import tool must be trados, memoq, or phrase.',
        ];
    }
}
