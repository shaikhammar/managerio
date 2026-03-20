<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'rate' => 'required|numeric|between:0,100',
            'description' => 'nullable|string',
        ]);

        TaxCode::create($validated);

        return redirect()->route('accounting.tax-codes.index')
            ->with('success', 'Tax code created successfully.');
    }

    public function edit(TaxCode $taxCode)
    {
        return Inertia::render('accounting/tax-codes/create', [
            'taxCode' => $taxCode,
        ]);
    }

    public function update(Request $request, TaxCode $taxCode)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'rate' => 'required|numeric|between:0,100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $taxCode->update($validated);

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
