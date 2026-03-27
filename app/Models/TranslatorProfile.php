<?php

namespace App\Models;

use App\Domain\Translation\Enums\CatTool;
use App\Domain\Translation\Enums\TranslatorAvailability;
use App\Domain\Translation\Enums\TranslatorCertification;
use App\Domain\Translation\Enums\TranslatorSpecialisation;
use App\Models\Concerns\BelongsToBusiness;
use Database\Factories\TranslatorProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TranslatorProfile extends Model
{
    /** @use HasFactory<TranslatorProfileFactory> */
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'contact_id',
        'availability',
        'quality_rating',
        'quality_notes',
        'specialisations',
        'cat_tools',
        'certifications',
    ];

    protected function casts(): array
    {
        return [
            'availability' => TranslatorAvailability::class,
            'quality_rating' => 'integer',
            'specialisations' => 'array',
            'cat_tools' => 'array',
            'certifications' => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /** @return BelongsToMany<LanguagePair, $this> */
    public function languagePairs(): BelongsToMany
    {
        return $this->belongsToMany(LanguagePair::class, 'translator_profile_language_pairs');
    }

    /** @return BelongsToMany<ServiceType, $this> */
    public function serviceTypes(): BelongsToMany
    {
        return $this->belongsToMany(ServiceType::class, 'translator_profile_service_types');
    }

    // ── Helpers ────────────────────────────────────────────────────

    /** @return list<TranslatorSpecialisation> */
    public function specialisationEnums(): array
    {
        return array_map(
            fn (string $v) => TranslatorSpecialisation::from($v),
            $this->specialisations ?? []
        );
    }

    /** @return list<CatTool> */
    public function catToolEnums(): array
    {
        return array_map(
            fn (string $v) => CatTool::from($v),
            $this->cat_tools ?? []
        );
    }

    /** @return list<TranslatorCertification> */
    public function certificationEnums(): array
    {
        return array_map(
            fn (string $v) => TranslatorCertification::from($v),
            $this->certifications ?? []
        );
    }
}
