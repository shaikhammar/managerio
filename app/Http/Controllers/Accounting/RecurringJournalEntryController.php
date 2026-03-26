<?php

namespace App\Http\Controllers\Accounting;

use App\Domain\Accounting\Enums\RecurringFrequency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\RecurringJournalEntryRequest;
use App\Models\Account;
use App\Models\RecurringJournalEntry;
use App\Services\Accounting\RecurringJournalService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecurringJournalEntryController extends Controller
{
    public function __construct(
        private RecurringJournalService $recurringJournalService,
    ) {}

    public function index(Request $request)
    {
        $entries = RecurringJournalEntry::query()
            ->with('creator')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%"))
            ->when($request->frequency, fn ($q, $f) => $q->where('frequency', $f))
            ->orderBy('name')
            ->paginate(25);

        return Inertia::render('accounting/recurring-journal-entries/index', [
            'entries' => $entries,
            'filters' => $request->only('search', 'frequency'),
            'frequencies' => collect(RecurringFrequency::cases())->map(fn ($f) => ['value' => $f->value, 'label' => $f->label()]),
        ]);
    }

    public function create()
    {
        return Inertia::render('accounting/recurring-journal-entries/create', [
            'accounts' => Account::query()->active()->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'frequencies' => collect(RecurringFrequency::cases())->map(fn ($f) => ['value' => $f->value, 'label' => $f->label()]),
        ]);
    }

    public function store(RecurringJournalEntryRequest $request)
    {
        $entry = $this->recurringJournalService->create(
            business: $request->user()->currentBusiness(),
            data: $request->validated(),
        );

        return redirect()->route('accounting.recurring-journal-entries.show', $entry)
            ->with('success', 'Recurring journal entry created.');
    }

    public function show(RecurringJournalEntry $recurringJournalEntry)
    {
        return Inertia::render('accounting/recurring-journal-entries/show', [
            'entry' => $recurringJournalEntry->load(['creator']),
            'accounts' => Account::query()
                ->whereIn('id', collect($recurringJournalEntry->template_lines)->pluck('account_id'))
                ->get(['id', 'code', 'name'])
                ->keyBy('id'),
        ]);
    }

    public function edit(RecurringJournalEntry $recurringJournalEntry)
    {
        return Inertia::render('accounting/recurring-journal-entries/edit', [
            'entry' => $recurringJournalEntry,
            'accounts' => Account::query()->active()->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'frequencies' => collect(RecurringFrequency::cases())->map(fn ($f) => ['value' => $f->value, 'label' => $f->label()]),
        ]);
    }

    public function update(RecurringJournalEntryRequest $request, RecurringJournalEntry $recurringJournalEntry)
    {
        $this->recurringJournalService->update($recurringJournalEntry, $request->validated());

        return redirect()->route('accounting.recurring-journal-entries.show', $recurringJournalEntry)
            ->with('success', 'Recurring journal entry updated.');
    }

    public function destroy(RecurringJournalEntry $recurringJournalEntry)
    {
        $recurringJournalEntry->delete();

        return redirect()->route('accounting.recurring-journal-entries.index')
            ->with('success', 'Recurring journal entry deleted.');
    }

    public function toggleActive(RecurringJournalEntry $recurringJournalEntry)
    {
        $recurringJournalEntry->update(['is_active' => ! $recurringJournalEntry->is_active]);

        $status = $recurringJournalEntry->is_active ? 'activated' : 'paused';

        return back()->with('success', "Recurring journal entry {$status}.");
    }
}
