<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NumberSequence extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'type',
        'prefix',
        'next_number',
        'padding',
    ];

    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
            'padding' => 'integer',
        ];
    }

    /**
     * Generate the next formatted number and increment the counter.
     * Example: INV-0001, QT-0042
     */
    public function generateNext(): string
    {
        $number = $this->prefix
            ? $this->prefix.'-'.str_pad((string) $this->next_number, $this->padding, '0', STR_PAD_LEFT)
            : str_pad((string) $this->next_number, $this->padding, '0', STR_PAD_LEFT);

        $this->increment('next_number');

        return $number;
    }
}
