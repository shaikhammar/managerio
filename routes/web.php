<?php

use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\TaxCodeController;
use App\Http\Controllers\Banking\BankAccountController;
use App\Http\Controllers\Banking\BankReconciliationController;
use App\Http\Controllers\Banking\BankTransactionController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Payments\ReceiptController;
use App\Http\Controllers\Payments\SupplierPaymentController;
use App\Http\Controllers\Purchases\DebitNoteController;
use App\Http\Controllers\Purchases\PurchaseInvoiceController;
use App\Http\Controllers\Purchases\PurchaseOrderController;
use App\Http\Controllers\Purchases\SupplierController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Sales\CreditNoteController;
use App\Http\Controllers\Sales\CustomerController;
use App\Http\Controllers\Sales\InvoiceController;
use App\Http\Controllers\Sales\QuoteController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// ── Public / Marketing Routes ─────────────────────────────────
Route::inertia('/', 'marketing/home', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::inertia('/features', 'marketing/features')->name('features');
Route::inertia('/pricing', 'marketing/pricing')->name('pricing');
Route::inertia('/about', 'marketing/about')->name('about');
Route::inertia('/docs', 'docs/getting-started')->name('docs');

// ── Authenticated Routes ──────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Business selection (no business context required)
    Route::get('/business', [BusinessController::class, 'index'])->name('business.index');
    Route::get('/business/create', [BusinessController::class, 'create'])->name('business.create');
    Route::post('/business', [BusinessController::class, 'store'])->name('business.store');
    Route::post('/business/{business}/switch', [BusinessController::class, 'switch'])->name('business.switch');

    // All routes below require a current business
    Route::middleware(['business.set', 'business.access'])->group(function () {

        // ── Dashboard ──────────────────────────────────────
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        // ── Accounting ─────────────────────────────────────
        Route::prefix('accounting')->name('accounting.')->group(function () {
            Route::resource('accounts', AccountController::class);
            Route::resource('journal-entries', JournalEntryController::class);
            Route::post('journal-entries/{journal_entry}/post', [JournalEntryController::class, 'post'])->name('journal-entries.post');
            Route::post('journal-entries/{journal_entry}/reverse', [JournalEntryController::class, 'reverse'])->name('journal-entries.reverse');
            Route::resource('tax-codes', TaxCodeController::class)->except(['show']);
        });

        // ── Sales ──────────────────────────────────────────
        Route::prefix('sales')->name('sales.')->group(function () {
            Route::resource('customers', CustomerController::class);
            Route::resource('quotes', QuoteController::class);
            Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert'])->name('quotes.convert');
            Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
            Route::resource('invoices', InvoiceController::class);
            Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
            Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
            Route::resource('credit-notes', CreditNoteController::class);
            Route::get('credit-notes/{credit_note}/pdf', [CreditNoteController::class, 'pdf'])->name('credit-notes.pdf');
        });

        // ── Purchases ──────────────────────────────────────
        Route::prefix('purchases')->name('purchases.')->group(function () {
            Route::resource('suppliers', SupplierController::class);
            Route::resource('invoices', PurchaseInvoiceController::class)->names('purchase-invoices');
            Route::resource('debit-notes', DebitNoteController::class);
            Route::get('debit-notes/{debit_note}/pdf', [DebitNoteController::class, 'pdf'])->name('debit-notes.pdf');
            Route::resource('purchase-orders', PurchaseOrderController::class);
            Route::post('purchase-orders/{purchase_order}/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
            Route::post('purchase-orders/{purchase_order}/convert', [PurchaseOrderController::class, 'convert'])->name('purchase-orders.convert');
            Route::get('purchase-orders/{purchase_order}/pdf', [PurchaseOrderController::class, 'pdf'])->name('purchase-orders.pdf');
        });

        // ── Payments ───────────────────────────────────────
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::resource('receipts', ReceiptController::class)->only(['index', 'create', 'store', 'show']);
            Route::resource('supplier-payments', SupplierPaymentController::class)->only(['index', 'create', 'store', 'show']);
        });

        // ── Banking ────────────────────────────────────────
        Route::prefix('banking')->name('banking.')->group(function () {
            Route::resource('accounts', BankAccountController::class)->only(['index', 'create', 'store', 'show']);
            Route::resource('transactions', BankTransactionController::class);
            Route::resource('reconciliations', BankReconciliationController::class);
        });

        // ── Reports ────────────────────────────────────────
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/profit-and-loss', [ReportController::class, 'profitAndLoss'])->name('profit-and-loss');
            Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('balance-sheet');
            Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->name('trial-balance');
            Route::get('/general-ledger', [ReportController::class, 'generalLedger'])->name('general-ledger');
            Route::get('/aged-receivables', [ReportController::class, 'agedReceivables'])->name('aged-receivables');
            Route::get('/aged-payables', [ReportController::class, 'agedPayables'])->name('aged-payables');
            Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
            Route::get('/status', [ReportController::class, 'status'])->name('status');
        });
    });
});

require __DIR__.'/settings.php';
