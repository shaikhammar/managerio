<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'language_pair_id',
        'service_type_id',
        'word_count',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'word_count' => 'integer',
            'unit_price' => 'decimal:4',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function languagePair(): BelongsTo
    {
        return $this->belongsTo(LanguagePair::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    public function catAnalyses(): HasMany
    {
        return $this->hasMany(CatAnalysis::class)->orderByDesc('created_at');
    }

    public function lineTotal(): string
    {
        if ($this->word_count === null || $this->unit_price === null) {
            return '0.00';
        }

        return bcmul((string) $this->word_count, (string) $this->unit_price, 2);
    }
}
