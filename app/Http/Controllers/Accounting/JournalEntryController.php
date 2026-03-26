<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\JournalEntryRequest;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalEntryController extends Controller
{
    public function __construct(
        private JournalService $journalService,
    ) {}

    public function index(Request $request): Response
    {
        $entries = JournalEntry::query()
            ->with('creator')
            ->when($request->search, fn ($q, $s) => $q->where('entry_number', 'ilike', "%{$s}%")->orWhere('description', 'ilike', "%{$s}%"))
            ->when($request->status, function ($q, $status) {
                $q->where('is_posted', $status === 'posted');
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25);

        return Inertia::render('accounting/journal-entries/index', [
            'journalEntries' => $entries,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('accounting/journal-entries/create', [
            'accounts' => Account::query()->active()->orderBy('code')->get(['id', 'code', 'name', 'type']),
        ]);
    }

    public function store(JournalEntryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $business = $request->user()->currentBusiness();

        $entry = $this->journalService->createDraft(
            business: $business,
            date: $validated['date'],
            lines: $validated['lines'],
            description: $validated['description'],
            reference: $validated['reference'] ?? null,
        );

        return redirect()->route('accounting.journal-entries.show', $entry)
            ->with('success', 'Journal entry created as draft.');
    }

    public function show(JournalEntry $journalEntry): Response
    {
        return Inertia::render('accounting/journal-entries/show', [
            'entry' => $journalEntry->load(['lines.account', 'lines.contact', 'creator', 'reversalOf', 'reversals']),
        ]);
    }

    public function post(JournalEntry $journalEntry): RedirectResponse
    {
        $this->journalService->post($journalEntry);

        return back()->with('success', 'Journal entry posted successfully.');
    }

    public function reverse(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        $validated = $request->validate(['reason' => 'required|string|max:255']);

        $this->journalService->reverse($journalEntry, $validated['reason']);

        return back()->with('success', 'Journal entry reversed successfully.');
    }
}
