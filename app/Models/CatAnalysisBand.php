<?php

namespace App\Models;

use App\Domain\Translation\Enums\CatMatchBand;
use Database\Factories\CatAnalysisBandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatAnalysisBand extends Model
{
    /** @use HasFactory<CatAnalysisBandFactory> */
    use HasFactory;

    protected $fillable = [
        'cat_analysis_id',
        'band',
        'words',
        'discount_percent',
    ];

    protected function casts(): array
    {
        return [
            'band' => CatMatchBand::class,
            'words' => 'integer',
            'discount_percent' => 'decimal:2',
        ];
    }

    public function catAnalysis(): BelongsTo
    {
        return $this->belongsTo(CatAnalysis::class);
    }

    public function effectiveWords(): string
    {
        return bcmul(
            (string) $this->words,
            bcdiv(bcsub('100', (string) $this->discount_percent, 2), '100', 4),
            2
        );
    }
}
