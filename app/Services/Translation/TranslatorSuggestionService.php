<?php

namespace App\Services\Translation;

use App\Domain\Translation\Enums\TranslatorAvailability;
use App\Models\Business;
use App\Models\TranslatorProfile;
use Illuminate\Support\Collection;

class TranslatorSuggestionService
{
    /**
     * Return up to 5 translator profiles ranked by availability + quality.
     *
     * @return Collection<int, array{contact_id: int, name: string, availability: string, quality_rating: int|null, score: int}>
     */
    public function suggest(Business $business, int $languagePairId, int $serviceTypeId): Collection
    {
        return TranslatorProfile::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereHas('languagePairs', fn ($q) => $q->where('language_pairs.id', $languagePairId))
            ->whereHas('serviceTypes', fn ($q) => $q->where('service_types.id', $serviceTypeId))
            ->with('contact')
            ->get()
            ->map(fn (TranslatorProfile $profile) => [
                'contact_id' => $profile->contact_id,
                'name' => $profile->contact?->name ?? 'Unknown',
                'availability' => $profile->availability->value,
                'quality_rating' => $profile->quality_rating,
                'score' => $this->score($profile),
            ])
            ->sortByDesc('score')
            ->take(5)
            ->values();
    }

    private function score(TranslatorProfile $profile): int
    {
        $availabilityScore = match ($profile->availability) {
            TranslatorAvailability::Available => 20,
            TranslatorAvailability::Busy => 10,
            TranslatorAvailability::OnLeave => 0,
        };

        return $availabilityScore + ($profile->quality_rating ?? 0);
    }
}
