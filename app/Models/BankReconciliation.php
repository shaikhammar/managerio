<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliation extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'bank_account_id',
        'statement_date',
        'statement_balance',
        'reconciled_balance',
        'is_completed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'statement_date' => 'date',
            'statement_balance' => 'decimal:2',
            'reconciled_balance' => 'decimal:2',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function difference(): float
    {
        return (float) $this->statement_balance - (float) $this->reconciled_balance;
    }

    public function isBalanced(): bool
    {
        return bccomp((string) $this->statement_balance, (string) $this->reconciled_balance, 2) === 0;
    }
}
