<?php

namespace App\Http\Requests\Accounting;

use App\Domain\Accounting\Enums\RecurringFrequency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecurringJournalEntryRequest extends FormRequest
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
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'frequency' => ['required', Rule::enum(RecurringFrequency::class)],
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'day_of_month' => 'required|integer|min:1|max:28',
            'template_lines' => 'required|array|min:2',
            'template_lines.*.account_id' => 'required|exists:accounts,id',
            'template_lines.*.debit' => 'required|numeric|min:0',
            'template_lines.*.credit' => 'required|numeric|min:0',
            'template_lines.*.description' => 'nullable|string|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'template_lines.min' => 'A recurring entry must have at least 2 lines.',
            'end_date.after' => 'The end date must be after the start date.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $lines = $this->input('template_lines', []);

            if (! is_array($lines)) {
                return;
            }

            $totalDebit = collect($lines)->sum(fn ($l) => (float) ($l['debit'] ?? 0));
            $totalCredit = collect($lines)->sum(fn ($l) => (float) ($l['credit'] ?? 0));

            if (bccomp((string) $totalDebit, (string) $totalCredit, 2) !== 0) {
                $v->errors()->add('template_lines', 'Template lines are unbalanced. Debits must equal credits.');
            }
        });
    }
}
