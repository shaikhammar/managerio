<?php

namespace App\Http\Controllers\Translation;

use App\Domain\Translation\Enums\BillingUnit;
use App\Domain\Translation\Enums\RateCardType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\RateCardRequest;
use App\Models\Contact;
use App\Models\LanguagePair;
use App\Models\RateCard;
use App\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RateCardController extends Controller
{
    public function index(Request $request): Response
    {
        $rateCards = RateCard::query()
            ->with(['contact', 'languagePair.sourceLanguage', 'languagePair.targetLanguage', 'serviceType', 'volumeTiers'])
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->search, function ($q, $search) {
                $lower = strtolower($search);
                $q->whereHas('contact', fn ($c) => $c->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"]))
                    ->orWhereHas('serviceType', fn ($s) => $s->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"]));
            })
            ->orderBy('type')
            ->orderBy('language_pair_id')
            ->paginate(25);

        return Inertia::render('translation/rate-cards/index', [
            'rateCards' => $rateCards,
            'filters' => $request->only('search', 'type'),
            'rateCardTypes' => collect(RateCardType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('translation/rate-cards/create', [
            'languagePairs' => LanguagePair::with(['sourceLanguage', 'targetLanguage'])->active()->get(),
            'serviceTypes' => ServiceType::active()->orderBy('name')->get(['id', 'name', 'default_unit']),
            'contacts' => Contact::active()->orderBy('name')->get(['id', 'name', 'type']),
            'rateCardTypes' => collect(RateCardType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'billingUnits' => collect(BillingUnit::cases())->map(fn ($u) => ['value' => $u->value, 'label' => $u->label()]),
        ]);
    }

    public function store(RateCardRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tiers = $validated['volume_tiers'] ?? [];
        unset($validated['volume_tiers']);

        if ($validated['type'] === RateCardType::Default->value) {
            $validated['contact_id'] = null;
        }

        $rateCard = RateCard::create($validated);

        foreach ($tiers as $tier) {
            $rateCard->volumeTiers()->create($tier);
        }

        return redirect()->route('translation.rate-cards.index')
            ->with('success', 'Rate card created successfully.');
    }

    public function edit(RateCard $rateCard): Response
    {
        $rateCard->load(['volumeTiers', 'languagePair.sourceLanguage', 'languagePair.targetLanguage', 'serviceType', 'contact']);

        return Inertia::render('translation/rate-cards/create', [
            'rateCard' => $rateCard,
            'languagePairs' => LanguagePair::with(['sourceLanguage', 'targetLanguage'])->active()->get(),
            'serviceTypes' => ServiceType::active()->orderBy('name')->get(['id', 'name', 'default_unit']),
            'contacts' => Contact::active()->orderBy('name')->get(['id', 'name', 'type']),
            'rateCardTypes' => collect(RateCardType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'billingUnits' => collect(BillingUnit::cases())->map(fn ($u) => ['value' => $u->value, 'label' => $u->label()]),
        ]);
    }

    public function update(RateCardRequest $request, RateCard $rateCard): RedirectResponse
    {
        $validated = $request->validated();
        $tiers = $validated['volume_tiers'] ?? [];
        unset($validated['volume_tiers']);

        if ($validated['type'] === RateCardType::Default->value) {
            $validated['contact_id'] = null;
        }

        $rateCard->update($validated);

        $rateCard->volumeTiers()->delete();
        foreach ($tiers as $tier) {
            $rateCard->volumeTiers()->create($tier);
        }

        return redirect()->route('translation.rate-cards.index')
            ->with('success', 'Rate card updated successfully.');
    }

    public function destroy(RateCard $rateCard): RedirectResponse
    {
        $rateCard->delete();

        return redirect()->route('translation.rate-cards.index')
            ->with('success', 'Rate card deleted successfully.');
    }
}
