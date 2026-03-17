<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JournalEntryController extends Controller
{
    public function __construct(
        private JournalService $journalService,
    ) {}

    public function index(Request $request)
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

    public function create()
    {
        return Inertia::render('accounting/journal-entries/create', [
            'accounts' => Account::query()->active()->orderBy('code')->get(['id', 'code', 'name', 'type']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ]);

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

    public function show(JournalEntry $journalEntry)
    {
        return Inertia::render('accounting/journal-entries/show', [
            'entry' => $journalEntry->load(['lines.account', 'lines.contact', 'creator', 'reversalOf', 'reversals']),
        ]);
    }

    public function post(JournalEntry $journalEntry)
    {
        $this->journalService->post($journalEntry);

        return back()->with('success', 'Journal entry posted successfully.');
    }

    public function reverse(Request $request, JournalEntry $journalEntry)
    {
        $validated = $request->validate(['reason' => 'required|string|max:255']);

        $this->journalService->reverse($journalEntry, $validated['reason']);

        return back()->with('success', 'Journal entry reversed successfully.');
    }
}
