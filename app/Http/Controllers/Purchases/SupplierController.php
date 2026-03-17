<?php

namespace App\Http\Controllers\Purchases;

use App\Domain\Contacts\Enums\ContactType;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Contact::query()
            ->suppliers()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%"))
            ->orderBy('name')
            ->paginate(25);

        return Inertia::render('purchases/suppliers/index', [
            'suppliers' => $suppliers,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('purchases/suppliers/create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'tax_number' => 'nullable|string|max:100',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|size:2',
            'notes' => 'nullable|string',
        ]);

        $validated['type'] = ContactType::SUPPLIER;
        Contact::create($validated);

        return redirect()->route('purchases.suppliers.index')->with('success', 'Supplier created successfully.');
    }

    public function show(Contact $supplier)
    {
        return Inertia::render('purchases/suppliers/show', ['supplier' => $supplier]);
    }

    public function edit(Contact $supplier)
    {
        return Inertia::render('purchases/suppliers/edit', ['supplier' => $supplier]);
    }

    public function update(Request $request, Contact $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $supplier->update($validated);

        return redirect()->route('purchases.suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Contact $supplier)
    {
        $supplier->delete();

        return redirect()->route('purchases.suppliers.index')->with('success', 'Supplier deleted.');
    }
}
