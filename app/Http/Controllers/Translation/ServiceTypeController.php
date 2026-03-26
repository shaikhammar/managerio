<?php

namespace App\Http\Controllers\Translation;

use App\Domain\Translation\Enums\BillingUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\ServiceTypeRequest;
use App\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $serviceTypes = ServiceType::query()
            ->when($request->search, function ($q, $search) {
                $lower = strtolower($search);
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"])
                    ->orWhereRaw('LOWER(code) LIKE ?', ["%{$lower}%"]);
            })
            ->orderBy('name')
            ->paginate(25);

        return Inertia::render('translation/service-types/index', [
            'serviceTypes' => $serviceTypes,
            'filters' => $request->only('search'),
            'billingUnits' => collect(BillingUnit::cases())->map(fn ($u) => ['value' => $u->value, 'label' => $u->label()]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('translation/service-types/create', [
            'billingUnits' => collect(BillingUnit::cases())->map(fn ($u) => ['value' => $u->value, 'label' => $u->label()]),
        ]);
    }

    public function store(ServiceTypeRequest $request): RedirectResponse
    {
        ServiceType::create($request->validated());

        return redirect()->route('translation.service-types.index')
            ->with('success', 'Service type created successfully.');
    }

    public function edit(ServiceType $serviceType): Response
    {
        return Inertia::render('translation/service-types/create', [
            'serviceType' => $serviceType,
            'billingUnits' => collect(BillingUnit::cases())->map(fn ($u) => ['value' => $u->value, 'label' => $u->label()]),
        ]);
    }

    public function update(ServiceTypeRequest $request, ServiceType $serviceType): RedirectResponse
    {
        $serviceType->update($request->validated());

        return redirect()->route('translation.service-types.index')
            ->with('success', 'Service type updated successfully.');
    }

    public function destroy(ServiceType $serviceType): RedirectResponse
    {
        $serviceType->delete();

        return redirect()->route('translation.service-types.index')
            ->with('success', 'Service type deleted successfully.');
    }
}
