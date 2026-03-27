<?php

namespace App\Http\Controllers\Translation;

use App\Domain\Translation\Enums\CatTool;
use App\Domain\Translation\Enums\TranslatorAvailability;
use App\Domain\Translation\Enums\TranslatorCertification;
use App\Domain\Translation\Enums\TranslatorSpecialisation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\TranslatorProfileRequest;
use App\Models\Contact;
use App\Models\LanguagePair;
use App\Models\ServiceType;
use App\Models\TranslatorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TranslatorProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $translators = TranslatorProfile::query()
            ->with(['contact', 'languagePairs.sourceLanguage', 'languagePairs.targetLanguage', 'serviceTypes'])
            ->when($request->search, function ($q, $search) {
                $lower = strtolower($search);
                $q->whereHas('contact', fn ($c) => $c->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"]));
            })
            ->when($request->availability, fn ($q, $av) => $q->where('availability', $av))
            ->orderByDesc('id')
            ->paginate(25);

        return Inertia::render('translation/translators/index', [
            'translators' => $translators,
            'filters' => $request->only('search', 'availability'),
            'availabilities' => collect(TranslatorAvailability::cases())->map(fn ($a) => [
                'value' => $a->value,
                'label' => $a->label(),
                'color' => $a->color(),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('translation/translators/create', $this->formData());
    }

    public function store(TranslatorProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $languagePairIds = $validated['language_pair_ids'] ?? [];
        $serviceTypeIds = $validated['service_type_ids'] ?? [];
        unset($validated['language_pair_ids'], $validated['service_type_ids']);

        $profile = TranslatorProfile::create($validated);
        $profile->languagePairs()->sync($languagePairIds);
        $profile->serviceTypes()->sync($serviceTypeIds);

        return redirect()->route('translation.translators.show', $profile)
            ->with('success', 'Translator profile created successfully.');
    }

    public function show(TranslatorProfile $translator): Response
    {
        $translator->load([
            'contact',
            'languagePairs.sourceLanguage',
            'languagePairs.targetLanguage',
            'serviceTypes',
        ]);

        return Inertia::render('translation/translators/show', [
            'translator' => $translator,
            'availabilities' => collect(TranslatorAvailability::cases())->map(fn ($a) => [
                'value' => $a->value,
                'label' => $a->label(),
                'color' => $a->color(),
            ]),
            'specialisations' => collect(TranslatorSpecialisation::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'catTools' => collect(CatTool::cases())->filter(fn ($t) => $t !== CatTool::Manual)->map(fn ($t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
            'certifications' => collect(TranslatorCertification::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
        ]);
    }

    public function edit(TranslatorProfile $translator): Response
    {
        $translator->load([
            'contact',
            'languagePairs',
            'serviceTypes',
        ]);

        return Inertia::render('translation/translators/create', array_merge(
            $this->formData(),
            ['translator' => $translator]
        ));
    }

    public function update(TranslatorProfileRequest $request, TranslatorProfile $translator): RedirectResponse
    {
        $validated = $request->validated();
        $languagePairIds = $validated['language_pair_ids'] ?? [];
        $serviceTypeIds = $validated['service_type_ids'] ?? [];
        unset($validated['language_pair_ids'], $validated['service_type_ids']);

        $translator->update($validated);
        $translator->languagePairs()->sync($languagePairIds);
        $translator->serviceTypes()->sync($serviceTypeIds);

        return redirect()->route('translation.translators.show', $translator)
            ->with('success', 'Translator profile updated successfully.');
    }

    public function destroy(TranslatorProfile $translator): RedirectResponse
    {
        $translator->delete();

        return redirect()->route('translation.translators.index')
            ->with('success', 'Translator profile deleted successfully.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'contacts' => Contact::active()->suppliers()->orderBy('name')->get(['id', 'name']),
            'languagePairs' => LanguagePair::with(['sourceLanguage', 'targetLanguage'])->active()->get(),
            'serviceTypes' => ServiceType::active()->orderBy('name')->get(['id', 'name']),
            'availabilities' => collect(TranslatorAvailability::cases())->map(fn ($a) => [
                'value' => $a->value,
                'label' => $a->label(),
            ]),
            'specialisations' => collect(TranslatorSpecialisation::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'catTools' => collect(CatTool::cases())->filter(fn ($t) => $t !== CatTool::Manual)->map(fn ($t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
            'certifications' => collect(TranslatorCertification::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
        ];
    }
}
