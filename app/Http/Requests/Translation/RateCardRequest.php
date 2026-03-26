<?php

namespace App\Http\Requests\Translation;

use App\Domain\Translation\Enums\BillingUnit;
use App\Domain\Translation\Enums\RateCardType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RateCardRequest extends FormRequest
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
        $rateCardId = $this->route('rate_card')?->id;

        $contactRequired = $this->input('type') !== RateCardType::Default->value
            ? 'required'
            : 'nullable';

        $uniqueRule = Rule::unique('rate_cards')
            ->where('business_id', $businessId)
            ->where('type', $this->input('type'))
            ->where('contact_id', $this->input('contact_id') ?: null)
            ->where('language_pair_id', $this->input('language_pair_id'))
            ->where('service_type_id', $this->input('service_type_id'))
            ->ignore($rateCardId);

        return [
            'type' => ['required', Rule::enum(RateCardType::class)],
            'contact_id' => [
                $contactRequired,
                'nullable',
                Rule::exists('contacts', 'id')->where('business_id', $businessId),
            ],
            'language_pair_id' => [
                'required',
                Rule::exists('language_pairs', 'id')->where('business_id', $businessId),
            ],
            'service_type_id' => [
                'required',
                Rule::exists('service_types', 'id')->where('business_id', $businessId),
                $uniqueRule,
            ],
            'unit_rate' => 'required|numeric|min:0',
            'unit' => ['required', Rule::enum(BillingUnit::class)],
            'minimum_fee' => 'nullable|numeric|min:0',
            'rush_multiplier' => 'nullable|numeric|min:1|max:10',
            'rush_fixed_surcharge' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'volume_tiers' => 'nullable|array',
            'volume_tiers.*.minimum_words' => 'required|integer|min:1',
            'volume_tiers.*.unit_rate' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'contact_id.required' => 'A contact is required for client and translator rate cards.',
            'service_type_id.unique' => 'A rate card for this type, contact, language pair, and service type already exists.',
        ];
    }
}
