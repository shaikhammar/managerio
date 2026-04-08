<?php

namespace App\Domain\Accounting\Enums;

enum AccountSubType: string
{
    // Asset sub-types
    case BANK = 'bank';
    case CASH = 'cash';
    case ACCOUNTS_RECEIVABLE = 'accounts_receivable';
    case TAX_RECEIVABLE = 'tax_receivable';
    case PREPAID_EXPENSE = 'prepaid_expense';
    case OTHER_CURRENT_ASSET = 'other_current_asset';
    case FIXED_ASSET = 'fixed_asset';

    // Liability sub-types
    case ACCOUNTS_PAYABLE = 'accounts_payable';
    case TAX_PAYABLE = 'tax_payable';
    case CREDIT_CARD = 'credit_card';
    case OTHER_CURRENT_LIABILITY = 'other_current_liability';
    case LONG_TERM_LIABILITY = 'long_term_liability';

    // Equity sub-types
    case OWNER_EQUITY = 'owner_equity';
    case RETAINED_EARNINGS = 'retained_earnings';
    case INTERCOMPANY = 'intercompany';

    // Revenue sub-types
    case SALES_REVENUE = 'sales_revenue';
    case SERVICE_REVENUE = 'service_revenue';
    case OTHER_REVENUE = 'other_revenue';

    // Expense sub-types
    case COST_OF_SERVICES = 'cost_of_services';
    case OPERATING_EXPENSE = 'operating_expense';
    case PAYROLL_EXPENSE = 'payroll_expense';
    case DEPRECIATION = 'depreciation';
    case OTHER_EXPENSE = 'other_expense';

    // Multi-currency
    case FOREX_GAIN_LOSS = 'forex_gain_loss';

    public function label(): string
    {
        return str_replace('_', ' ', ucwords($this->value, '_'));
    }

    public function accountType(): AccountType
    {
        return match ($this) {
            self::BANK, self::CASH, self::ACCOUNTS_RECEIVABLE,
            self::TAX_RECEIVABLE, self::PREPAID_EXPENSE,
            self::OTHER_CURRENT_ASSET, self::FIXED_ASSET => AccountType::ASSET,

            self::ACCOUNTS_PAYABLE, self::TAX_PAYABLE, self::CREDIT_CARD,
            self::OTHER_CURRENT_LIABILITY, self::LONG_TERM_LIABILITY => AccountType::LIABILITY,

            self::OWNER_EQUITY, self::RETAINED_EARNINGS, self::INTERCOMPANY => AccountType::EQUITY,

            self::SALES_REVENUE, self::SERVICE_REVENUE, self::OTHER_REVENUE => AccountType::REVENUE,

            self::COST_OF_SERVICES, self::OPERATING_EXPENSE, self::PAYROLL_EXPENSE,
            self::DEPRECIATION, self::OTHER_EXPENSE => AccountType::EXPENSE,

            self::FOREX_GAIN_LOSS => AccountType::REVENUE,
        };
    }
}
