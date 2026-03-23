<?php

namespace App\Http\Controllers\Sales;

use App\Domain\Contacts\Enums\ContactType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\ContactRequest;
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

    public function store(ContactRequest $request)
    {
        $this->authorize('create', Contact::class);

        Contact::create(array_merge($request->validated(), ['type' => ContactType::CUSTOMER]));

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

    public function update(ContactRequest $request, Contact $customer)
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return redirect()->route('sales.customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Contact $customer)
    {
        $this->authorize('delete', $customer);

        if ($customer->invoices()->exists()) {
            return back()->with('error', 'Cannot delete customer with existing invoices.');
        }

        $customer->delete();

        return redirect()->route('sales.customers.index')
            ->with('success', 'Customer deleted successfully.');
    }
}
