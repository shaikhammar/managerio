<?php

namespace App\Http\Controllers\Accounting;

use App\Domain\Accounting\Enums\AssetStatus;
use App\Domain\Accounting\Enums\DepreciationMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\DepreciationRunRequest;
use App\Http\Requests\Accounting\FixedAssetDisposalRequest;
use App\Http\Requests\Accounting\FixedAssetRequest;
use App\Models\Account;
use App\Models\FixedAsset;
use App\Services\Accounting\FixedAssetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FixedAssetController extends Controller
{
    public function __construct(
        private FixedAssetService $fixedAssetService,
    ) {}

    public function index(Request $request)
    {
        $assets = FixedAsset::query()
            ->with(['assetAccount', 'creator'])
            ->when($request->search, fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('name')
            ->paginate(25);

        // Append computed book value to each asset
        $assets->getCollection()->transform(function (FixedAsset $asset) {
            $asset->append([]);
            $asset->setAttribute('accumulated_depreciation', $asset->accumulatedDepreciation());
            $asset->setAttribute('book_value', $asset->bookValue());

            return $asset;
        });

        return Inertia::render('accounting/fixed-assets/index', [
            'assets' => $assets,
            'filters' => $request->only('search', 'status'),
            'statuses' => collect(AssetStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function create()
    {
        return Inertia::render('accounting/fixed-assets/create', [
            'accounts' => Account::query()->active()->orderBy('code')->get(['id', 'code', 'name', 'type', 'sub_type']),
            'methods' => collect(DepreciationMethod::cases())->map(fn ($m) => ['value' => $m->value, 'label' => $m->label()]),
        ]);
    }

    public function store(FixedAssetRequest $request)
    {
        $asset = $this->fixedAssetService->create(
            business: $request->user()->currentBusiness(),
            data: $request->validated(),
        );

        return redirect()->route('accounting.fixed-assets.show', $asset)
            ->with('success', 'Fixed asset registered.');
    }

    public function show(FixedAsset $fixedAsset)
    {
        $fixedAsset->load(['assetAccount', 'accumulatedDepreciationAccount', 'depreciationExpenseAccount', 'creator', 'depreciationEntries.journalEntry']);

        $schedule = $this->fixedAssetService->calculateSchedule($fixedAsset);

        return Inertia::render('accounting/fixed-assets/show', [
            'asset' => array_merge($fixedAsset->toArray(), [
                'accumulated_depreciation' => $fixedAsset->accumulatedDepreciation(),
                'book_value' => $fixedAsset->bookValue(),
                'is_fully_depreciated' => $fixedAsset->isFullyDepreciated(),
            ]),
            'schedule' => $schedule,
        ]);
    }

    public function edit(FixedAsset $fixedAsset)
    {
        return Inertia::render('accounting/fixed-assets/edit', [
            'asset' => $fixedAsset,
            'accounts' => Account::query()->active()->orderBy('code')->get(['id', 'code', 'name', 'type', 'sub_type']),
            'methods' => collect(DepreciationMethod::cases())->map(fn ($m) => ['value' => $m->value, 'label' => $m->label()]),
        ]);
    }

    public function update(FixedAssetRequest $request, FixedAsset $fixedAsset)
    {
        $this->fixedAssetService->update($fixedAsset, $request->validated());

        return redirect()->route('accounting.fixed-assets.show', $fixedAsset)
            ->with('success', 'Fixed asset updated.');
    }

    public function destroy(FixedAsset $fixedAsset)
    {
        if ($fixedAsset->depreciationEntries()->exists()) {
            return back()->with('error', 'Cannot delete an asset with depreciation history. Dispose of it instead.');
        }

        $fixedAsset->delete();

        return redirect()->route('accounting.fixed-assets.index')
            ->with('success', 'Fixed asset deleted.');
    }

    public function retire(FixedAsset $fixedAsset)
    {
        $this->fixedAssetService->retire($fixedAsset);

        return back()->with('success', 'Asset retired.');
    }

    public function dispose(FixedAssetDisposalRequest $request, FixedAsset $fixedAsset)
    {
        $this->fixedAssetService->dispose(
            asset: $fixedAsset,
            business: $request->user()->currentBusiness(),
            data: $request->validated(),
        );

        return redirect()->route('accounting.fixed-assets.show', $fixedAsset)
            ->with('success', 'Asset disposed and journal entry posted.');
    }

    public function runDepreciation(DepreciationRunRequest $request)
    {
        $periodStart = Carbon::createFromFormat('Y-m', $request->validated('period'))->startOfMonth();

        $result = $this->fixedAssetService->runDepreciation(
            business: $request->user()->currentBusiness(),
            periodStart: $periodStart,
        );

        $message = "Depreciation run complete: {$result['processed']} asset(s) processed, {$result['skipped']} skipped.";

        return back()->with('success', $message);
    }
}
