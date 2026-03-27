<?php

namespace App\Models;

use App\Domain\Translation\Enums\CatMatchBand;
use App\Domain\Translation\Enums\CatTool;
use App\Models\Concerns\BelongsToBusiness;
use Database\Factories\CatAnalysisFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatAnalysis extends Model
{
    /** @use HasFactory<CatAnalysisFactory> */
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'project_target_id',
        'name',
        'tool',
    ];

    protected function casts(): array
    {
        return [
            'tool' => CatTool::class,
        ];
    }

    public function projectTarget(): BelongsTo
    {
        return $this->belongsTo(ProjectTarget::class);
    }

    public function bands(): HasMany
    {
        $order = "CASE band
            WHEN 'context_match' THEN 1
            WHEN 'exact_match' THEN 2
            WHEN 'fuzzy_95_99' THEN 3
            WHEN 'fuzzy_85_94' THEN 4
            WHEN 'fuzzy_75_84' THEN 5
            WHEN 'fuzzy_50_74' THEN 6
            WHEN 'no_match' THEN 7
            WHEN 'repetitions' THEN 8
            ELSE 9 END";

        return $this->hasMany(CatAnalysisBand::class)->orderByRaw($order);
    }

    public function totalWords(): int
    {
        return (int) $this->bands->sum('words');
    }

    public function weightedWords(): string
    {
        $weighted = '0.00';

        foreach ($this->bands as $band) {
            $effective = bcmul(
                (string) $band->words,
                bcdiv(bcsub('100', (string) $band->discount_percent, 2), '100', 4),
                2
            );
            $weighted = bcadd($weighted, $effective, 2);
        }

        return $weighted;
    }

    /** @return array<string, array{words: int, discount_percent: string}> */
    public function bandMap(): array
    {
        return $this->bands->keyBy('band')->map(fn ($b) => [
            'words' => $b->words,
            'discount_percent' => $b->discount_percent,
        ])->all();
    }

    public function initDefaultBands(): void
    {
        foreach (CatMatchBand::cases() as $band) {
            $this->bands()->firstOrCreate(
                ['band' => $band->value],
                ['words' => 0, 'discount_percent' => $band->defaultDiscountPercent()]
            );
        }
    }
}
