<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\TaxCodeRequest;
use App\Models\TaxCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxCodeController extends Controller
{
    public function index(Request $request): Response
    {
        $taxCodes = TaxCode::query()
            ->when($request->search, fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->paginate(25);

        return Inertia::render('accounting/tax-codes/index', [
            'taxCodes' => $taxCodes,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('accounting/tax-codes/create');
    }

    public function store(TaxCodeRequest $request): RedirectResponse
    {
        TaxCode::create($request->validated());

        return redirect()->route('accounting.tax-codes.index')
            ->with('success', 'Tax code created successfully.');
    }

    public function edit(TaxCode $taxCode): Response
    {
        return Inertia::render('accounting/tax-codes/create', [
            'taxCode' => $taxCode,
        ]);
    }

    public function update(TaxCodeRequest $request, TaxCode $taxCode): RedirectResponse
    {
        $taxCode->update($request->validated());

        return redirect()->route('accounting.tax-codes.index')
            ->with('success', 'Tax code updated successfully.');
    }

    public function destroy(TaxCode $taxCode): RedirectResponse
    {
        $taxCode->delete();

        return redirect()->route('accounting.tax-codes.index')
            ->with('success', 'Tax code deleted successfully.');
    }
}
