<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\IntercompanyRequest;
use App\Models\Account;
use App\Models\Business;
use App\Models\IntercompanyTransaction;
use App\Services\Accounting\IntercompanyService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IntercompanyController extends Controller
{
    public function __construct(
        private IntercompanyService $intercompanyService,
    ) {}

    public function index(Request $request)
    {
        $business = $request->user()->currentBusiness();
        $transactions = $this->intercompanyService->listForBusiness($business);

        return Inertia::render('accounting/intercompany/index', [
            'transactions' => $transactions,
        ]);
    }

    public function create(Request $request)
    {
        $business = $request->user()->currentBusiness();

        $sourceAccounts = Account::query()
            ->active()
            ->orderBy('type')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);

        $otherBusinesses = $request->user()->businesses()
            ->where('businesses.id', '!=', $business->id)
            ->get(['businesses.id', 'businesses.name']);

        return Inertia::render('accounting/intercompany/create', [
            'sourceAccounts' => $sourceAccounts,
            'otherBusinesses' => $otherBusinesses,
        ]);
    }

    public function store(IntercompanyRequest $request)
    {
        $validated = $request->validated();
        $business = $request->user()->currentBusiness();

        $transaction = $this->intercompanyService->transfer($business, $validated);

        return redirect()
            ->route('accounting.intercompany.show', $transaction->id)
            ->with('success', 'Intercompany transfer recorded.');
    }

    public function show(Request $request, int $id)
    {
        $business = $request->user()->currentBusiness();

        $transaction = IntercompanyTransaction::query()
            ->where(function ($q) use ($business) {
                $q->where('source_business_id', $business->id)
                    ->orWhere('target_business_id', $business->id);
            })
            ->with([
                'sourceBusiness',
                'targetBusiness',
                'sourceAccount',
                'targetAccount',
                'sourceJournalEntry',
                'targetJournalEntry',
                'creator',
            ])
            ->findOrFail($id);

        return Inertia::render('accounting/intercompany/show', [
            'transaction' => $transaction,
        ]);
    }

    /**
     * Fetch accounts for a given target business (used via AJAX on the create form).
     */
    public function targetAccounts(Request $request)
    {
        $request->validate(['business_id' => 'required|exists:businesses,id']);

        $accounts = Account::withoutGlobalScopes()
            ->where('business_id', $request->business_id)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);

        return response()->json($accounts);
    }
}
