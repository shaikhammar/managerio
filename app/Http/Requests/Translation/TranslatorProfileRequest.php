<?php

namespace App\Http\Requests\Translation;

use App\Domain\Translation\Enums\CatTool;
use App\Domain\Translation\Enums\TranslatorAvailability;
use App\Domain\Translation\Enums\TranslatorCertification;
use App\Domain\Translation\Enums\TranslatorSpecialisation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TranslatorProfileRequest extends FormRequest
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
        $profileId = $this->route('translator')?->id;

        return [
            'contact_id' => [
                'required',
                'integer',
                Rule::exists('contacts', 'id')->where('business_id', $businessId),
                Rule::unique('translator_profiles', 'contact_id')
                    ->where('business_id', $businessId)
                    ->ignore($profileId),
            ],
            'availability' => ['required', Rule::enum(TranslatorAvailability::class)],
            'quality_rating' => 'nullable|integer|min:1|max:5',
            'quality_notes' => 'nullable|string|max:1000',
            'specialisations' => 'nullable|array',
            'specialisations.*' => [Rule::enum(TranslatorSpecialisation::class)],
            'cat_tools' => 'nullable|array',
            'cat_tools.*' => [Rule::enum(CatTool::class)],
            'certifications' => 'nullable|array',
            'certifications.*' => [Rule::enum(TranslatorCertification::class)],
            'language_pair_ids' => 'nullable|array',
            'language_pair_ids.*' => [
                'integer',
                Rule::exists('language_pairs', 'id')->where('business_id', $businessId),
            ],
            'service_type_ids' => 'nullable|array',
            'service_type_ids.*' => [
                'integer',
                Rule::exists('service_types', 'id')->where('business_id', $businessId),
            ],
        ];
    }
}
