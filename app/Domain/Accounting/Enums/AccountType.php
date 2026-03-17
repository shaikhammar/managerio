<?php

namespace App\Domain\Accounting\Enums;

enum AccountType: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case REVENUE = 'revenue';
    case EXPENSE = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::ASSET => 'Asset',
            self::LIABILITY => 'Liability',
            self::EQUITY => 'Equity',
            self::REVENUE => 'Revenue',
            self::EXPENSE => 'Expense',
        };
    }

    /**
     * Returns the normal balance side for this account type.
     * Assets and Expenses normally have debit balances.
     * Liabilities, Equity, and Revenue normally have credit balances.
     */
    public function normalBalance(): string
    {
        return match ($this) {
            self::ASSET, self::EXPENSE => 'debit',
            self::LIABILITY, self::EQUITY, self::REVENUE => 'credit',
        };
    }

    /**
     * Whether this account type appears on the Balance Sheet.
     */
    public function isBalanceSheet(): bool
    {
        return match ($this) {
            self::ASSET, self::LIABILITY, self::EQUITY => true,
            self::REVENUE, self::EXPENSE => false,
        };
    }
}
