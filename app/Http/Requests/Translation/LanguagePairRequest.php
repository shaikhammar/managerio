<?php

namespace App\Http\Requests\Translation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanguagePairRequest extends FormRequest
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
        $pairId = $this->route('language_pair')?->id;

        return [
            'source_language_id' => [
                'required',
                'integer',
                Rule::exists('languages', 'id')->where('business_id', $businessId),
            ],
            'target_language_id' => [
                'required',
                'integer',
                'different:source_language_id',
                Rule::exists('languages', 'id')->where('business_id', $businessId),
                Rule::unique('language_pairs', 'target_language_id')
                    ->where('business_id', $businessId)
                    ->where('source_language_id', $this->source_language_id)
                    ->ignore($pairId),
            ],
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'target_language_id.different' => 'Source and target language must be different.',
            'target_language_id.unique' => 'This language pair already exists.',
        ];
    }
}
