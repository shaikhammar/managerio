<?php

use App\Http\Controllers\Portal\InvoicePortalController;
use App\Http\Controllers\Portal\ProjectPortalController;
use App\Http\Controllers\Portal\QuotePortalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['signed'])->prefix('portal')->name('portal.')->group(function (): void {
    Route::get('/quotes/{quote}', [QuotePortalController::class, 'show'])->name('quotes.show');
    Route::post('/quotes/{quote}/approve', [QuotePortalController::class, 'approve'])->name('quotes.approve');
    Route::post('/quotes/{quote}/reject', [QuotePortalController::class, 'reject'])->name('quotes.reject');

    Route::get('/invoices/{invoice}', [InvoicePortalController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/pdf', [InvoicePortalController::class, 'pdf'])->name('invoices.pdf');

    Route::get('/projects/{project}', [ProjectPortalController::class, 'show'])->name('projects.show');
});
