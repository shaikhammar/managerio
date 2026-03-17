<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CreditNoteController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('sales/credit-notes/index');
    }

    public function create()
    {
        return Inertia::render('sales/credit-notes/create');
    }

    public function store(Request $request)
    {
        return back();
    }

    public function show($id)
    {
        return Inertia::render('sales/credit-notes/show');
    }

    public function edit($id)
    {
        return Inertia::render('sales/credit-notes/edit');
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
