<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\TaxCodeRequest;
use App\Models\TaxCode;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaxCodeController extends Controller
{
    public function index(Request $request)
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

    public function create()
    {
        return Inertia::render('accounting/tax-codes/create');
    }

    public function store(TaxCodeRequest $request)
    {
        TaxCode::create($request->validated());

        return redirect()->route('accounting.tax-codes.index')
            ->with('success', 'Tax code created successfully.');
    }

    public function edit(TaxCode $taxCode)
    {
        return Inertia::render('accounting/tax-codes/create', [
            'taxCode' => $taxCode,
        ]);
    }

    public function update(TaxCodeRequest $request, TaxCode $taxCode)
    {
        $taxCode->update($request->validated());

        return redirect()->route('accounting.tax-codes.index')
            ->with('success', 'Tax code updated successfully.');
    }

    public function destroy(TaxCode $taxCode)
    {
        $taxCode->delete();

        return redirect()->route('accounting.tax-codes.index')
            ->with('success', 'Tax code deleted successfully.');
    }
}
