<?php

namespace App\Services\Business;

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Shared\Enums\BusinessRole;
use App\Events\BusinessCreated;
use App\Models\Account;
use App\Models\Business;
use App\Models\NumberSequence;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BusinessSetupService
{
    /**
     * Create a new business with default chart of accounts, tax codes, and number sequences.
     */
    public function createBusiness(User $owner, array $data): Business
    {
        return DB::transaction(function () use ($owner, $data) {
            $business = Business::create([
                'name' => $data['name'],
                'legal_name' => $data['legal_name'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address_line_1' => $data['address_line_1'] ?? null,
                'address_line_2' => $data['address_line_2'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? 'US',
                'currency_code' => $data['currency_code'] ?? 'USD',
                'fiscal_year_start' => $data['fiscal_year_start'] ?? 1,
            ]);

            // Attach owner
            $business->users()->attach($owner->id, ['role' => BusinessRole::OWNER->value]);

            // Create default chart of accounts
            $this->createDefaultAccounts($business);

            // Create number sequences
            $this->createNumberSequences($business);

            BusinessCreated::dispatch($business, $owner);

            return $business;
        });
    }

    private function createDefaultAccounts(Business $business): void
    {
        $accounts = [
            // ── Assets ─────────────────────────────────────────
            ['1000', 'Cash on Hand', AccountType::ASSET, AccountSubType::CASH, true],
            ['1010', 'Bank Account', AccountType::ASSET, AccountSubType::BANK, true],
            ['1100', 'Accounts Receivable', AccountType::ASSET, AccountSubType::ACCOUNTS_RECEIVABLE, true],
            ['1200', 'Tax Receivable', AccountType::ASSET, AccountSubType::TAX_RECEIVABLE, true],
            ['1300', 'Prepaid Expenses', AccountType::ASSET, AccountSubType::PREPAID_EXPENSE, false],

            // ── Liabilities ────────────────────────────────────
            ['2000', 'Accounts Payable', AccountType::LIABILITY, AccountSubType::ACCOUNTS_PAYABLE, true],
            ['2100', 'Tax Payable', AccountType::LIABILITY, AccountSubType::TAX_PAYABLE, true],
            ['2200', 'Credit Card', AccountType::LIABILITY, AccountSubType::CREDIT_CARD, false],
            ['2300', 'Other Current Liabilities', AccountType::LIABILITY, AccountSubType::OTHER_CURRENT_LIABILITY, false],

            // ── Equity ─────────────────────────────────────────
            ['3000', 'Owner\'s Equity', AccountType::EQUITY, AccountSubType::OWNER_EQUITY, true],
            ['3100', 'Retained Earnings', AccountType::EQUITY, AccountSubType::RETAINED_EARNINGS, true],

            // ── Revenue ────────────────────────────────────────
            ['4000', 'Sales Revenue', AccountType::REVENUE, AccountSubType::SALES_REVENUE, true],
            ['4100', 'Service Revenue', AccountType::REVENUE, AccountSubType::SERVICE_REVENUE, false],
            ['4200', 'Other Revenue', AccountType::REVENUE, AccountSubType::OTHER_REVENUE, false],

            // ── Expenses ───────────────────────────────────────
            ['5000', 'Cost of Services', AccountType::EXPENSE, AccountSubType::COST_OF_SERVICES, false],
            ['6000', 'Advertising & Marketing', AccountType::EXPENSE, AccountSubType::OPERATING_EXPENSE, false],
            ['6100', 'Bank Charges & Fees', AccountType::EXPENSE, AccountSubType::OPERATING_EXPENSE, false],
            ['6200', 'Insurance', AccountType::EXPENSE, AccountSubType::OPERATING_EXPENSE, false],
            ['6300', 'Office Supplies', AccountType::EXPENSE, AccountSubType::OPERATING_EXPENSE, false],
            ['6400', 'Professional Fees', AccountType::EXPENSE, AccountSubType::OPERATING_EXPENSE, false],
            ['6500', 'Rent', AccountType::EXPENSE, AccountSubType::OPERATING_EXPENSE, false],
            ['6600', 'Salaries & Wages', AccountType::EXPENSE, AccountSubType::PAYROLL_EXPENSE, false],
            ['6700', 'Software & Subscriptions', AccountType::EXPENSE, AccountSubType::OPERATING_EXPENSE, false],
            ['6800', 'Telephone & Internet', AccountType::EXPENSE, AccountSubType::OPERATING_EXPENSE, false],
            ['6900', 'Travel & Transportation', AccountType::EXPENSE, AccountSubType::OPERATING_EXPENSE, false],
            ['7000', 'Utilities', AccountType::EXPENSE, AccountSubType::OPERATING_EXPENSE, false],
            ['7100', 'Depreciation', AccountType::EXPENSE, AccountSubType::DEPRECIATION, false],
            ['7200', 'Miscellaneous Expenses', AccountType::EXPENSE, AccountSubType::OTHER_EXPENSE, false],

            // ── Multi-currency ────────────────────────────────
            ['8000', 'Foreign Exchange Gain/Loss', AccountType::REVENUE, AccountSubType::FOREX_GAIN_LOSS, true],
        ];

        foreach ($accounts as [$code, $name, $type, $subType, $isSystem]) {
            Account::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'sub_type' => $subType,
                'is_system' => $isSystem,
                'is_active' => true,
            ]);
        }
    }

    private function createNumberSequences(Business $business): void
    {
        $sequences = [
            ['invoice', 'INV', 1, 4],
            ['quote', 'QT', 1, 4],
            ['credit_note', 'CN', 1, 4],
            ['purchase_invoice', 'PI', 1, 4],
            ['payment', 'PAY', 1, 4],
            ['receipt', 'REC', 1, 4],
            ['journal_entry', 'JE', 1, 4],
        ];

        foreach ($sequences as [$type, $prefix, $nextNumber, $padding]) {
            NumberSequence::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'type' => $type,
                'prefix' => $prefix,
                'next_number' => $nextNumber,
                'padding' => $padding,
            ]);
        }
    }
}
