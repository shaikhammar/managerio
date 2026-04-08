<?php

namespace App\Http\Requests\Accounting;

use App\Domain\Shared\Enums\BusinessRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $business = $this->user()->currentBusiness();
        $role = $business->users()->where('user_id', $this->user()->id)->first()?->pivot->role;

        return in_array($role, [BusinessRole::OWNER->value, BusinessRole::ADMIN->value, BusinessRole::EDITOR->value]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $businessId = session('current_business_id');
        $exchangeRateId = $this->route('exchange_rate')?->id;

        $baseCurrency = $this->user()->currentBusiness()->currency_code;

        return [
            'currency_code' => ['required', 'string', 'size:3', Rule::notIn([$baseCurrency])],
            'rate' => ['required', 'numeric', 'min:0.000001'],
            'date' => [
                'required',
                'date',
                Rule::unique('exchange_rates')
                    ->where('business_id', $businessId)
                    ->where('currency_code', $this->input('currency_code'))
                    ->ignore($exchangeRateId),
            ],
        ];
    }
}
