<?php

namespace App\Http\Controllers\Purchases;

use App\Domain\Contacts\Enums\ContactType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\ContactRequest;
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

    public function store(ContactRequest $request)
    {
        $this->authorize('create', Contact::class);

        Contact::create(array_merge($request->validated(), ['type' => ContactType::SUPPLIER]));

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

    public function update(ContactRequest $request, Contact $supplier)
    {
        $this->authorize('update', $supplier);

        $supplier->update($request->validated());

        return redirect()->route('purchases.suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Contact $supplier)
    {
        $this->authorize('delete', $supplier);

        $supplier->delete();

        return redirect()->route('purchases.suppliers.index')->with('success', 'Supplier deleted.');
    }
}
