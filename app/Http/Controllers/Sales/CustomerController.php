<?php

namespace App\Http\Controllers\Sales;

use App\Domain\Contacts\Enums\ContactType;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Contact::query()
            ->customers()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%")->orWhere('email', 'ilike', "%{$s}%"))
            ->orderBy('name')
            ->paginate(25);

        return Inertia::render('sales/customers/index', [
            'customers' => $customers,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('sales/customers/create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'tax_number' => 'nullable|string|max:100',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|size:2',
            'notes' => 'nullable|string',
        ]);

        $validated['type'] = ContactType::CUSTOMER;

        Contact::create($validated);

        return redirect()->route('sales.customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function show(Contact $customer)
    {
        return Inertia::render('sales/customers/show', [
            'customer' => $customer->load(['invoices' => fn ($q) => $q->latest('date')->limit(10)]),
        ]);
    }

    public function edit(Contact $customer)
    {
        return Inertia::render('sales/customers/edit', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Contact $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'tax_number' => 'nullable|string|max:100',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|size:2',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $customer->update($validated);

        return redirect()->route('sales.customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Contact $customer)
    {
        if ($customer->invoices()->exists()) {
            return back()->with('error', 'Cannot delete customer with existing invoices.');
        }

        $customer->delete();

        return redirect()->route('sales.customers.index')
            ->with('success', 'Customer deleted successfully.');
    }
}
