<?php

namespace App\Models;

use App\Domain\Translation\Enums\ProjectStatus;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'contact_id',
        'source_language_id',
        'service_type_id',
        'name',
        'reference',
        'deadline',
        'notes',
        'deadline_alert_sent_at',
        'status',
        'quote_id',
        'invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'deadline' => 'date',
            'deadline_alert_sent_at' => 'date',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function sourceLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'source_language_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'quote_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(ProjectTarget::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class)->orderBy('created_at');
    }

    public function assignments(): HasManyThrough
    {
        return $this->hasManyThrough(ProjectAssignment::class, ProjectTarget::class);
    }

    /** @return BelongsToMany<TranslationMemory, $this> */
    public function translationMemories(): BelongsToMany
    {
        return $this->belongsToMany(TranslationMemory::class, 'project_translation_memories');
    }

    /** @return BelongsToMany<TermBase, $this> */
    public function termBases(): BelongsToMany
    {
        return $this->belongsToMany(TermBase::class, 'project_term_bases');
    }

    /** @return BelongsToMany<StyleGuide, $this> */
    public function styleGuides(): BelongsToMany
    {
        return $this->belongsToMany(StyleGuide::class, 'project_style_guides');
    }

    public function scopeSearch($query, string $search)
    {
        $lower = strtolower($search);

        return $query->where(function ($q) use ($lower) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"])
                ->orWhereRaw('LOWER(reference) LIKE ?', ["%{$lower}%"]);
        });
    }

    public function scopeForLanguagePair($query, int $languagePairId)
    {
        return $query->whereHas('targets', fn ($q) => $q->where('language_pair_id', $languagePairId));
    }

    public function scopeForDeadlineRange($query, ?string $from, ?string $to)
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate('deadline', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('deadline', '<=', $to));
    }

    public function isEditable(): bool
    {
        return ! in_array($this->status, [ProjectStatus::CLOSED]);
    }

    public function canGenerateQuote(): bool
    {
        return $this->quote_id === null;
    }

    public function canGenerateInvoice(): bool
    {
        return in_array($this->status, [ProjectStatus::COMPLETED, ProjectStatus::DELIVERED])
            && $this->invoice_id === null;
    }
}
