<?php

namespace App\Http\Requests\Translation;

use App\Domain\Translation\Enums\ProjectAssignmentRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'contact_id' => [
                'required',
                Rule::exists('contacts', 'id')->where('business_id', $businessId),
            ],
            'source_language_id' => [
                'required',
                Rule::exists('languages', 'id')->where('business_id', $businessId),
            ],
            'service_type_id' => [
                'required',
                Rule::exists('service_types', 'id')->where('business_id', $businessId),
            ],
            'deadline' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string',
            'targets' => 'nullable|array',
            'targets.*.id' => 'nullable|integer',
            'targets.*.language_pair_id' => [
                'required',
                Rule::exists('language_pairs', 'id')->where('business_id', $businessId),
            ],
            'targets.*.service_type_id' => [
                'nullable',
                Rule::exists('service_types', 'id')->where('business_id', $businessId),
            ],
            'targets.*.word_count' => 'nullable|integer|min:1',
            'targets.*.unit_price' => 'nullable|numeric|min:0',
            'targets.*.assignments' => 'nullable|array',
            'targets.*.assignments.*.id' => 'nullable|integer',
            'targets.*.assignments.*.contact_id' => [
                'required',
                Rule::exists('contacts', 'id')->where('business_id', $businessId),
            ],
            'targets.*.assignments.*.role' => ['required', Rule::enum(ProjectAssignmentRole::class)],
            'targets.*.assignments.*.rate' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'targets.*.language_pair_id.required' => 'Each target must have a language pair.',
            'targets.*.assignments.*.contact_id.required' => 'Each assignment must have a contact.',
            'targets.*.assignments.*.role.required' => 'Each assignment must have a role.',
        ];
    }
}
