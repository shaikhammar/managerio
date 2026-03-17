<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('sales/quotes/index');
    }

    public function create()
    {
        return Inertia::render('sales/quotes/create');
    }

    public function store(Request $request)
    {
        return back();
    }

    public function show($id)
    {
        return Inertia::render('sales/quotes/show');
    }

    public function edit($id)
    {
        return Inertia::render('sales/quotes/edit');
    }

    public function update(Request $request, $id)
    {
        return back();
    }

    public function destroy($id)
    {
        return back();
    }

    public function convert($id)
    {
        return back();
    }
}
