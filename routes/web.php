<?php

use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\BudgetController;
use App\Http\Controllers\Accounting\FixedAssetController;
use App\Http\Controllers\Accounting\IntercompanyController;
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\OpeningBalanceController;
use App\Http\Controllers\Accounting\RecurringJournalEntryController;
use App\Http\Controllers\Accounting\TaxCodeController;
use App\Http\Controllers\AuditLogController;
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
use App\Http\Controllers\Sales\BulkInvoiceController;
use App\Http\Controllers\Sales\BulkQuoteController;
use App\Http\Controllers\Sales\CreditNoteController;
use App\Http\Controllers\Sales\CustomerController;
use App\Http\Controllers\Sales\InvoiceController;
use App\Http\Controllers\Sales\QuoteController;
use App\Http\Controllers\Translation\CatAnalysisController;
use App\Http\Controllers\Translation\LanguageController;
use App\Http\Controllers\Translation\LanguagePairController;
use App\Http\Controllers\Translation\ProjectController;
use App\Http\Controllers\Translation\RateCardController;
use App\Http\Controllers\Translation\ServiceTypeController;
use App\Http\Controllers\Translation\TranslatorProfileController;
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
            Route::resource('recurring-journal-entries', RecurringJournalEntryController::class);
            Route::post('recurring-journal-entries/{recurring_journal_entry}/toggle-active', [RecurringJournalEntryController::class, 'toggleActive'])->name('recurring-journal-entries.toggle-active');
            Route::get('opening-balances/create', [OpeningBalanceController::class, 'create'])->name('opening-balances.create');
            Route::post('opening-balances', [OpeningBalanceController::class, 'store'])->name('opening-balances.store');
            Route::get('budgets', [BudgetController::class, 'index'])->name('budgets.index');
            Route::get('budgets/edit', [BudgetController::class, 'edit'])->name('budgets.edit');
            Route::post('budgets', [BudgetController::class, 'save'])->name('budgets.save');
            Route::get('intercompany', [IntercompanyController::class, 'index'])->name('intercompany.index');
            Route::get('intercompany/create', [IntercompanyController::class, 'create'])->name('intercompany.create');
            Route::get('intercompany/target-accounts', [IntercompanyController::class, 'targetAccounts'])->name('intercompany.target-accounts');
            Route::post('intercompany', [IntercompanyController::class, 'store'])->name('intercompany.store');
            Route::get('intercompany/{id}', [IntercompanyController::class, 'show'])->name('intercompany.show');
            Route::resource('fixed-assets', FixedAssetController::class);
            Route::post('fixed-assets/{fixed_asset}/retire', [FixedAssetController::class, 'retire'])->name('fixed-assets.retire');
            Route::post('fixed-assets/{fixed_asset}/dispose', [FixedAssetController::class, 'dispose'])->name('fixed-assets.dispose');
            Route::post('fixed-assets/run-depreciation', [FixedAssetController::class, 'runDepreciation'])->name('fixed-assets.run-depreciation');
        });

        // ── Sales ──────────────────────────────────────────
        Route::prefix('sales')->name('sales.')->group(function () {
            Route::resource('customers', CustomerController::class);
            Route::resource('quotes', QuoteController::class);
            Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert'])->name('quotes.convert');
            Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
            Route::post('quotes/{quote}/send-email', [QuoteController::class, 'sendEmail'])->name('quotes.send-email');
            Route::delete('quotes/bulk/delete', [BulkQuoteController::class, 'deleteDrafts'])->name('quotes.bulk.delete');
            Route::resource('invoices', InvoiceController::class);
            Route::post('invoices/{invoice}/post', [InvoiceController::class, 'post'])->name('invoices.post');
            Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
            Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
            Route::post('invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail'])->name('invoices.send-email');
            Route::post('invoices/bulk/mark-sent', [BulkInvoiceController::class, 'markSent'])->name('invoices.bulk.mark-sent');
            Route::delete('invoices/bulk/delete', [BulkInvoiceController::class, 'deleteDrafts'])->name('invoices.bulk.delete');
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

        // ── Translation ────────────────────────────────────
        Route::prefix('translation')->name('translation.')->group(function () {
            Route::resource('languages', LanguageController::class)->except(['show']);
            Route::resource('language-pairs', LanguagePairController::class)->except(['show']);
            Route::resource('service-types', ServiceTypeController::class)->except(['show']);
            Route::resource('rate-cards', RateCardController::class)->except(['show']);
            Route::resource('translators', TranslatorProfileController::class);
            Route::resource('projects', ProjectController::class);
            Route::post('projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.status');
            Route::post('projects/{project}/generate-quote', [ProjectController::class, 'generateQuote'])->name('projects.generate-quote');
            Route::post('projects/{project}/generate-invoice', [ProjectController::class, 'generateInvoice'])->name('projects.generate-invoice');
            Route::post('projects/{project}/generate-purchase-orders', [ProjectController::class, 'generatePurchaseOrders'])->name('projects.generate-purchase-orders');
            Route::post('projects/{project}/files', [ProjectController::class, 'storeFile'])->name('projects.files.store');
            Route::delete('projects/{project}/files/{projectFile}', [ProjectController::class, 'destroyFile'])->name('projects.files.destroy');
            Route::get('projects/{project}/cat-analyses/create', [CatAnalysisController::class, 'create'])->name('projects.cat-analyses.create');
            Route::post('projects/{project}/cat-analyses', [CatAnalysisController::class, 'store'])->name('projects.cat-analyses.store');
            Route::post('projects/{project}/cat-analyses/import', [CatAnalysisController::class, 'import'])->name('projects.cat-analyses.import');
            Route::get('projects/{project}/cat-analyses/{catAnalysis}', [CatAnalysisController::class, 'show'])->name('projects.cat-analyses.show');
            Route::delete('projects/{project}/cat-analyses/{catAnalysis}', [CatAnalysisController::class, 'destroy'])->name('projects.cat-analyses.destroy');
            Route::post('projects/{project}/cat-analyses/{catAnalysis}/apply-quote', [CatAnalysisController::class, 'applyToQuote'])->name('projects.cat-analyses.apply-quote');
            Route::post('projects/{project}/cat-analyses/{catAnalysis}/apply-po', [CatAnalysisController::class, 'applyToPurchaseOrders'])->name('projects.cat-analyses.apply-po');
        });

        // ── Audit Log ──────────────────────────────────────
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

        // ── Reports ────────────────────────────────────────
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/profit-and-loss', [ReportController::class, 'profitAndLoss'])->name('profit-and-loss');
            Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('balance-sheet');
            Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->name('trial-balance');
            Route::get('/general-ledger', [ReportController::class, 'generalLedger'])->name('general-ledger');
            Route::get('/aged-receivables', [ReportController::class, 'agedReceivables'])->name('aged-receivables');
            Route::get('/aged-payables', [ReportController::class, 'agedPayables'])->name('aged-payables');
            Route::get('/account-transactions', [ReportController::class, 'accountTransactions'])->name('account-transactions');
            Route::get('/cash-flow', [ReportController::class, 'cashFlow'])->name('cash-flow');
            Route::get('/equity-statement', [ReportController::class, 'equityStatement'])->name('equity-statement');
            Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
            Route::get('/status', [ReportController::class, 'status'])->name('status');
        });
    });
});

require __DIR__.'/settings.php';
