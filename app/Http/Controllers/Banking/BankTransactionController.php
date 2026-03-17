<?php

namespace App\Http\Controllers\Banking;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankTransactionController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('banking/transactions/index');
    }

    public function create()
    {
        return Inertia::render('banking/transactions/create');
    }

    public function store(Request $request)
    {
        return back();
    }

    public function show(BankTransaction $transaction)
    {
        return Inertia::render('banking/transactions/show');
    }

    public function edit(BankTransaction $transaction)
    {
        return Inertia::render('banking/transactions/edit');
    }

    public function update(Request $request, BankTransaction $transaction)
    {
        return back();
    }

    public function destroy(BankTransaction $transaction)
    {
        return back();
    }
}
