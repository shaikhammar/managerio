<?php

namespace App\Http\Requests\Translation;

use App\Domain\Translation\Enums\ProjectFileType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectFileRequest extends FormRequest
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
            'file' => 'required|file|max:51200',
            'type' => ['required', Rule::enum(ProjectFileType::class)],
        ];
    }
}
