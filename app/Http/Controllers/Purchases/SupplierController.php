<?php

namespace App\Http\Controllers\Purchases;

use App\Domain\Contacts\Enums\ContactType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\ContactRequest;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
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

    public function create(): Response
    {
        return Inertia::render('purchases/suppliers/create');
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $this->authorize('create', Contact::class);

        Contact::create(array_merge($request->validated(), ['type' => ContactType::SUPPLIER]));

        return redirect()->route('purchases.suppliers.index')->with('success', 'Supplier created successfully.');
    }

    public function show(Contact $supplier): Response
    {
        return Inertia::render('purchases/suppliers/show', ['supplier' => $supplier]);
    }

    public function edit(Contact $supplier): Response
    {
        return Inertia::render('purchases/suppliers/edit', ['supplier' => $supplier]);
    }

    public function update(ContactRequest $request, Contact $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        $supplier->update($request->validated());

        return redirect()->route('purchases.suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Contact $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        $supplier->delete();

        return redirect()->route('purchases.suppliers.index')->with('success', 'Supplier deleted.');
    }
}
