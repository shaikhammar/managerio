<?php

namespace App\Http\Requests\Translation;

use App\Domain\Translation\Enums\BillingUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceTypeRequest extends FormRequest
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
        $serviceTypeId = $this->route('service_type')?->id;

        return [
            'name' => 'required|string|max:100',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('service_types', 'code')
                    ->where('business_id', $businessId)
                    ->ignore($serviceTypeId),
            ],
            'description' => 'nullable|string',
            'default_unit' => ['required', Rule::enum(BillingUnit::class)],
            'is_active' => 'sometimes|boolean',
        ];
    }
}
