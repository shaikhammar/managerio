<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseInvoiceController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('purchases/invoices/index');
    }

    public function create()
    {
        return Inertia::render('purchases/invoices/create');
    }

    public function store(Request $request)
    {
        return back();
    }

    public function show($id)
    {
        return Inertia::render('purchases/invoices/show');
    }

    public function edit($id)
    {
        return Inertia::render('purchases/invoices/edit');
    }

    public function update(Request $request, $id)
    {
        return back();
    }

    public function destroy($id)
    {
        return back();
    }
}
