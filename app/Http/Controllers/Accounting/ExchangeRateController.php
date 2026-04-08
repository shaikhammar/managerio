<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\ExchangeRateRequest;
use App\Models\ExchangeRate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExchangeRateController extends Controller
{
    public function index(): Response
    {
        $rates = ExchangeRate::query()
            ->orderByDesc('date')
            ->orderBy('currency_code')
            ->paginate(25);

        return Inertia::render('accounting/exchange-rates/index', [
            'rates' => $rates,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('accounting/exchange-rates/create');
    }

    public function store(ExchangeRateRequest $request): RedirectResponse
    {
        ExchangeRate::create($request->validated());

        return redirect()->route('accounting.exchange-rates.index')
            ->with('success', 'Exchange rate saved.');
    }

    public function edit(ExchangeRate $exchangeRate): Response
    {
        return Inertia::render('accounting/exchange-rates/create', [
            'rate' => $exchangeRate,
        ]);
    }

    public function update(ExchangeRateRequest $request, ExchangeRate $exchangeRate): RedirectResponse
    {
        $exchangeRate->update($request->validated());

        return redirect()->route('accounting.exchange-rates.index')
            ->with('success', 'Exchange rate updated.');
    }

    public function destroy(ExchangeRate $exchangeRate): RedirectResponse
    {
        $exchangeRate->delete();

        return redirect()->route('accounting.exchange-rates.index')
            ->with('success', 'Exchange rate deleted.');
    }
}
